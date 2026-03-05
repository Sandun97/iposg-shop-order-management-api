<?php

namespace App\Services;

use App\models\OrderItem;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function topProducts(array $filters)
    {
        return OrderItem::select(
                'product_id',
                'product_name',
                DB::raw('SUM(qty) as total_qty')
            )
            ->when($filters['shop_id'] ?? null, function ($query, $shopId) {
                $query->whereHas('order', function ($q) use ($shopId) {
                    $q->where('shop_id', $shopId);
                });
            })
            ->when($filters['from'] ?? null, function ($query, $from) {
                $query->whereHas('order', function ($q) use ($from) {
                    $q->whereDate('created_at', '>=', $from);
                });
            })
            ->when($filters['to'] ?? null, function ($query, $to) {
                $query->whereHas('order', function ($q) use ($to) {
                    $q->whereDate('created_at', '<=', $to);
                });
            })
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();
    }
}
