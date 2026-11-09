<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Mail\SaleTransferMail;
use App\Models\Plan;
use App\Models\Sale;
use App\Models\User;
use App\Support\MailConfig;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ConsultantController extends Controller
{
    private function guardAdmin()
    {
        if (Auth::user()->roles !== 'admin') {
            return redirect()->route('app')->with('infor', 'Acesso restrito ao administrador.');
        }

        return null;
    }

    public function index(Request $request)
    {
        if ($redirect = $this->guardAdmin()) {
            return $redirect;
        }

        $query = User::query()
            ->whereIn('roles', ['admin', 'collaborator'])
            ->withCount(['salesAsSeller']);

        if (!empty($request->search)) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhere('email', 'like', '%' . $term . '%')
                    ->orWhere('document', 'like', '%' . preg_replace('/\D/', '', $term) . '%');
            });
        }

        return view('app.Consultant.index', [
            'consultants' => $query->orderBy('name')->paginate(30)->withQueryString(),
            'filters'     => $request->only('search'),
            'plans'       => Plan::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function show(string $uuid)
    {
        if ($redirect = $this->guardAdmin()) {
            return $redirect;
        }

        $consultant = User::where('uuid', $uuid)
            ->whereIn('roles', ['admin', 'collaborator'])
            ->first();

        if (!$consultant) {
            return redirect()->route('consultants')->with('error', 'Consultor não encontrado.');
        }

        $sales = Sale::with(['user', 'plan', 'invoices'])
            ->where('seller_id', $consultant->id)
            ->whereHas('user')
            ->latest()
            ->paginate(30);

        $otherConsultants = User::whereIn('roles', ['admin', 'collaborator'])
            ->where('id', '!=', $consultant->id)
            ->orderBy('name')
            ->get();

        return view('app.Consultant.show', [
            'consultant'       => $consultant,
            'sales'            => $sales,
            'plans'            => Plan::where('status', 'active')->orderBy('name')->get(),
            'otherConsultants' => $otherConsultants,
        ]);
    }

    public function transfer(Request $request)
    {
        if ($redirect = $this->guardAdmin()) {
            return $redirect;
        }

        $validated = $request->validate([
            'sale_uuid'         => ['required', 'string'],
            'to_consultant_uuid'=> ['required', 'string', Rule::exists('users', 'uuid')],
        ], [
            'sale_uuid.required'          => 'Informe a venda/beneficiário.',
            'to_consultant_uuid.required' => 'Selecione o consultor de destino.',
            'to_consultant_uuid.exists'   => 'Consultor de destino inválido.',
        ]);

        $sale = Sale::with(['user', 'plan'])->where('uuid', $validated['sale_uuid'])->first();
        if (!$sale) {
            return redirect()->back()->with('error', 'Venda/beneficiário não encontrado.');
        }

        $to = User::where('uuid', $validated['to_consultant_uuid'])
            ->whereIn('roles', ['admin', 'collaborator'])
            ->first();

        if (!$to) {
            return redirect()->back()->with('error', 'Destino deve ser um consultor (admin ou collaborator).');
        }

        if ((int) $sale->seller_id === (int) $to->id) {
            return redirect()->back()->with('infor', 'Esta venda já está com esse consultor.');
        }

        $from = $sale->seller_id ? User::find($sale->seller_id) : null;
        $fromName = $from?->name ?? 'Sem consultor';
        $fromEmail = $from?->email;

        $sale->seller_id = $to->id;
        $sale->save();

        $this->sendTransferEmails($sale, $from, $to, Auth::user());

        Log::info('Transferência de beneficiário entre consultores', [
            'sale'   => $sale->uuid,
            'from'   => $from?->uuid,
            'to'     => $to->uuid,
            'admin'  => Auth::user()->uuid,
        ]);

        return redirect()->back()->with(
            'success',
            "Beneficiário transferido de {$fromName} para {$to->name}. Um e-mail de transparência foi enviado."
        );
    }

    private function sendTransferEmails(Sale $sale, ?User $from, User $to, User $admin): void
    {
        try {
            MailConfig::apply();
            if (!MailConfig::isConfigured()) {
                Log::warning('Transferência sem SMTP configurado', ['sale' => $sale->uuid]);
                return;
            }

            $payload = [
                'subject'        => 'Transferência de beneficiário — ' . config('app.name'),
                'fromEmail'      => MailConfig::fromAddress() ?: config('mail.from.address'),
                'fromName'       => MailConfig::fromName() ?: config('mail.from.name', config('app.name')),
                'admin_name'     => $admin->name,
                'buyer_name'     => $sale->user->name ?? 'Beneficiário',
                'buyer_document' => $sale->user?->maskCpfCnpj() ?? '—',
                'plan_name'      => $sale->plan->name ?? $sale->name,
                'from_name'      => $from?->name ?? 'Sem consultor',
                'to_name'        => $to->name,
                'transferred_at' => now()->format('d/m/Y H:i'),
            ];

            $recipients = collect([$admin->email, $to->email, $from?->email])
                ->filter()
                ->unique()
                ->values();

            foreach ($recipients as $email) {
                Mail::to($email)->send(new SaleTransferMail($payload));
            }
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar e-mail de transferência', [
                'sale'  => $sale->uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
