<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MerchantController extends Controller
{
    public function __construct(
        MerchantService $merchantService
    ) {}

    /**
     * Useful order statistics for the merchant API.
     *
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        $fromDate = $request->input('from');
        $toDate = $request->input('to');

        $count = DB::table('orders')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->count();

        $commissionOwed = DB::table('orders')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->sum('commission_owed');

        $revenue = DB::table('orders')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->sum('subtotal');

        return response()->json([
            'count' => $count,
            'commission_owed' => $commissionOwed,
            'revenue' => $revenue,
        ]);
        }
}
