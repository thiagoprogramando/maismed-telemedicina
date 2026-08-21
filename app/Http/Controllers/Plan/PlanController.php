<?php

namespace App\Http\Controllers\Plan;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class PlanController extends Controller {
    
    public function index (Request $request) {

        $query = Plan::orderBy('name', 'asc');

        if (!empty($request->name)) {
            $query->where('name', 'LIKE', '%'.$request->name.'%');
        }
        if (!empty($request->status)) {
            $query->where('status', $request->status);
        }
        if (!empty($request->time)) {
            $query->where('time', $request->time);
        }

        return view('app.Plan.index', [
            'plans' => $query->paginate(30)
        ]);
    }

    public function show ($uuid) {

        $plan = Plan::where('uuid', $uuid)->first();
        if (!$plan) {
            return redirect()->back()->with('infor', 'Plano indisponível!');
        }

        return view('app.Plan.show', [
            'plan' => $plan
        ]);
    }

    public function store (Request $request) {

        $plan               = new Plan();
        $plan->uuid         = Str::uuid();
        $plan->created_by   = Auth::user()->id;
        $plan->name         = $request->name;
        $plan->price        = $this->formatValue($request->price);
        $plan->commission   = $this->formatValue($request->commission);
        $plan->max_users    = $request->max_users;
        $plan->status       = $request->status;
        $plan->time         = $request->time;
        $plan->description  = $request->description;
        $plan->terms        = $request->terms;
        $plan->features     = $request->features;
        if ($plan->save()) {
            return redirect()->route('plan', ['uuid' => $plan->uuid])->with('success', 'Plano cadastrado com sucesso!');
        }

        return redirect()->back()->with('error', 'Falha ao cadastrar plano, verifique os dados e tente novamente!');
    }

    public function update (Request $request, $uuid) {

        $plan = Plan::where('uuid', $uuid)->first();
        if (!$plan) {
            return redirect()->back()->with('infor', 'Plano indisponível!');
        }

        if (!empty($request->name)) {
            $plan->name = $request->name;
        }
        if (!empty($request->price)) {
            $plan->price = $this->formatValue($request->price);
        }
        if (!empty($request->commission)) {
            $plan->commission = $this->formatValue($request->commission);
        }
        if (!empty($request->status)) {
            $plan->status = $request->status;
        }
        if (!empty($request->time)) {
            $plan->time = $request->time;
        }
        if (!empty($request->description)) {
            $plan->description = $request->description;
        }
        if (!empty($request->terms)) {
            $plan->terms = $request->terms;
        }
        if (!empty($request->max_users)) {
            $plan->max_users = $request->max_users;
        }
        if (!empty($request->features)) {
            $plan->features = $request->features;
        }

        if ($plan->save()) {
           return redirect()->route('plan', ['uuid' => $plan->uuid])->with('success', 'Plano atualizado com sucesso!');
        }

        return redirect()->back()->with('error', 'Falha ao atualizar plano, verifique os dados e tente novamente!');
    }

    public function destroy (Request $request, $uuid) {

        if (Hash::check($request->password, Auth::user()->password)) {

            $plan = Plan::where('uuid', $uuid)->first();
            if ($plan && $plan->delete()) {
                return redirect()->back()->with('success', 'Plano excluído com sucesso!');
            }

            return redirect()->back()->with('error', 'Não foi poss[ivel excluir o plano, verifique os dados e tente novamente!');
        }

        return redirect()->back()->with('error', 'Credenciais inválidas, verifique os dados e tente novamente!');
    }

    private function formatValue ($value) {
        
        $value = preg_replace('/[^0-9,]/', '', $value);
        $value = str_replace(',', '.', $value);
        $valueFloat = floatval($value);
    
        return number_format($valueFloat, 2, '.', '');
    }
}
