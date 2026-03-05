<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\TopProductResource;
use App\Services\ReportService;

class ReportController extends Controller
{
    public function topProducts(ReportService $reportService)
    {
        $products = $reportService->topProducts(
            request()->only(['shop_id', 'from', 'to'])
        );
        
        return TopProductResource::collection($products);
    }
}
