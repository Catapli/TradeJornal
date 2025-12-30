<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LogController extends Controller
{
    /**
     * Devuelve los registros de log en formato compatible con DataTables,
     * con filtros opcionales por municipio, usuario, acción y rango de fechas.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {

        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado.');
        }
        // Validar y obtener los parámetros de filtro
        $townFilter = $request->input('town');
        $userFilter = $request->input('user');
        $actionFilter = $request->input('action');
        $dateFilter = $request->input('date');

        // Construir la consulta base con relaciones y campos específicos
        $query = Log::select('id', 'user_id', 'town_id', 'form', 'action', 'created_at')
            ->with([
                'user:id,name',     // Solo carga 'id' y 'name' del usuario
                'town:id,town',     // Solo carga 'id' y 'town' del municipio
            ]);

        // Filtro por municipio
        if ($townFilter) {
            $query->where('town_id', $townFilter);
        }

        // Filtro por usuario
        if ($userFilter) {
            $query->where('user_id', $userFilter);
        }

        // Filtro por acción
        if ($actionFilter) {
            $query->where('action', $actionFilter);
        }

        // Filtro por rango de fechas (formato: "dd/mm/yyyy - dd/mm/yyyy")
        if ($dateFilter) {
            $dateRange = array_map('trim', explode('-', $dateFilter));

            if (count($dateRange) === 2) {
                try {
                    $startDate = Carbon::createFromFormat('d/m/Y', $dateRange[0])
                        ->startOfDay()
                        ->format('Y-m-d H:i:s');

                    $endDate = Carbon::createFromFormat('d/m/Y', $dateRange[1])
                        ->endOfDay()
                        ->format('Y-m-d H:i:s');

                    $query->whereBetween('created_at', [$startDate, $endDate]);
                } catch (InvalidFormatException $e) {
                    // Opcional: podrías registrar el error o ignorar el filtro
                    // En este caso, simplemente no aplicamos el filtro si el formato es inválido
                }
            }
        }

        // Obtener los resultados ordenados por fecha descendente
        $logs = $query->orderBy('created_at', 'desc')->get();

        return datatables()->of($logs)->make(true);
    }
}
