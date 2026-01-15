<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Trade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TradeController extends Controller
{
    //
    public function data(Request $request)
    {
        if (!$request->ajax()) {
            abort(403);
        }

        $idFilter = $request->input('id');

        // Seleccionamos todo lo necesario
        $trades = Trade::where('account_id', $idFilter)
            ->with('tradeAsset:id,symbol') // Traemos el símbolo
            ->select([
                'id',
                'ticket',
                'trade_asset_id',
                'direction', // Asegúrate de tener direction o type
                'size',
                'entry_price',
                'exit_price',
                'entry_time',
                'exit_time',
                'pnl'
            ]);

        return datatables()->of($trades)
            // Formateamos fechas para que lleguen bonitas al JS
            ->editColumn('entry_time', function ($row) {
                return $row->entry_time ? \Carbon\Carbon::parse($row->entry_time)->format('Y-m-d H:i:s') : '-';
            })
            ->editColumn('exit_time', function ($row) {
                return $row->exit_time ? \Carbon\Carbon::parse($row->exit_time)->format('Y-m-d H:i:s') : '-';
            })
            ->addColumn('symbol', function ($row) {
                return $row->tradeAsset->symbol ?? '---';
            })
            ->make(true);
    }

    public function dashboard(Request $request)
    {
        if (!$request->ajax()) {
            abort(403);
        }


        $idFilter = $request->input('accounts');

        // Log::info($idFilter);


        Log::info($idFilter);

        if (empty($idFilter) || $idFilter[0] === "all") {
            $authId = Auth::user()->id;
            $idFilter = Account::where('user_id', $authId)
                ->where('status', '!=', 'burned')
                ->pluck('id')
                ->toArray();
        }


        // Seleccionamos todo lo necesario
        $trades = Trade::whereIn('account_id', $idFilter)
            ->with('tradeAsset:id,symbol') // Traemos el símbolo
            ->select([
                'id',
                'trade_asset_id',
                'exit_time',
                'pnl'
            ]);

        return datatables()->of($trades)
            ->editColumn('exit_time', function ($row) {
                return $row->exit_time ? \Carbon\Carbon::parse($row->exit_time)->format('Y-m-d') : '-';
            })
            ->addColumn('symbol', function ($row) {
                return $row->tradeAsset->symbol ?? '---';
            })
            ->make(true);
    }
}
