<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Carbon\Carbon;
use App\Models\Sale;

class SaleController extends Controller {

    public function index (Request $request) {

        $validated = $request->validate([
            'parent_id'  => ['nullable', 'string'],
            'search'     => ['nullable', 'string', 'max:255'],
            'document'   => ['nullable', 'string', 'max:20'],
            'date_start' => ['nullable', 'date'],
            'date_end'   => ['nullable', 'date', 'after_or_equal:date_start'],
        ]);

        $user   = Auth::user();
        $query  = Sale::query();
    
        if ($user->roles === 'admin') {
            if (!empty($validated['parent_id'])) {
                $query->where('parent_id', $validated['parent_id']);
            }
        } else {
            $query->where('parent_id', $user->uuid);
        }
    
        if (!empty($validated['search'])) {
            $term = $this->escapeLikeTerm($validated['search']);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                ->orWhereHas('user', function ($userQuery) use ($term) {
                    $userQuery->where('name', 'like', "%{$term}%");
                });
            });
        }
    
        if (!empty($validated['document'])) {
            $documentDigits = preg_replace('/\D/', '', $validated['document']);
            if ($documentDigits !== '') {
                $query->whereHas('user', function ($userQuery) use ($documentDigits) {
                    $userQuery->whereRaw(
                        "REPLACE(REPLACE(REPLACE(document, '.', ''), '-', ''), '/', '') LIKE ?",
                        ["%{$documentDigits}%"]
                    );
                });
            }
        }
    
        if (!empty($validated['date_start'])) {
            $query->where('created_at', '>=', Carbon::parse($validated['date_start'])->startOfDay());
        }
        if (!empty($validated['date_end'])) {
            $query->where('created_at', '<=', Carbon::parse($validated['date_end'])->endOfDay());
        }
    
        $sales  = $query->with(['user', 'plan'])->latest()->paginate(30)->withQueryString();
 
        return view('app.Sale.index', [
            'sales'   => $sales,
            'filters' => $validated,
        ]);
    }
    
    public function show ($uuid) {

        $sale = Sale::with(['user', 'plan', 'invoices'])->where('uuid', $uuid)->firstOrFail();
        return view('app.Sale.show', [
            'sale' => $sale,
        ]);
    }

    private function escapeLikeTerm(string $term): string {
        return str_replace(['%', '_'], ['\\%', '\\_'], $term);
    }
}
