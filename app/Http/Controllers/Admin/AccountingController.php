<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function index(Request $request)
    {
        $transactions = Transaction::with('booking')
            ->when($request->type, fn($q,$v) => $q->where('type',$v))
            ->when($request->month, fn($q,$v) => $q->whereRaw('DATE_FORMAT(date,"%Y-%m") = ?', [$v]))
            ->latest('date')->paginate(20);

        $summary = [
            'total_revenue'    => Transaction::where('type','revenue')->sum('amount'),
            'total_expenses'   => Transaction::where('type','expense')->sum('amount'),
            'total_commission' => Transaction::where('type','commission')->sum('amount'),
            'total_refunds'    => Transaction::where('type','refund')->sum('amount'),
        ];
        $summary['net'] = $summary['total_revenue'] + $summary['total_commission'] - $summary['total_expenses'] - $summary['total_refunds'];

        return view('admin.accounting.index', compact('transactions','summary'));
    }
}