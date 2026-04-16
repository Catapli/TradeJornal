<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TradeChartController extends Controller
{
    public function __construct(private StorageService $storage) {}

    public function show(Trade $trade): JsonResponse
    {
        abort_if($trade->account->user_id !== Auth::id(), 403);
        abort_unless($trade->chart_data_path, 404);

        $data = $this->storage->getJson($trade->chart_data_path);
        abort_unless($data, 404);

        // ✅ Sin cache — cada apertura de trade lee directo desde R2
        return response()->json($data);
    }
}
