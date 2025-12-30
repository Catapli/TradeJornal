<?php

namespace App\Http\Controllers;

use App\AuthActions;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    use AuthActions;
    /**
     * Devuelve los usuarios en formato compatible con DataTables,
     * con filtros opcionales por municipio, nombre y estado activo.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(Request $request)
    {

        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado.');
        }

        $townFilter = $request->input('town');
        $nameFilter = $request->input('name');
        $activeFilter = $request->input('active');
        $user = Auth::user();
        // Construir la consulta base con relación y campos específicos
        $query = User::with('town:id,town')
            ->select('id', 'name', 'town_id', 'active');

        // Filtro por municipio
        // if ($townFilter) {
        //     $query->where('town_id', $townFilter);
        // }
        // Filtro por municipio (town) si es superadmin
        if ($this->isSuperAdmin($user)) {
            if ($townFilter) {
                $query->where('town_id', $townFilter);
            }
        } else {
            $query->where('town_id', $user->town_id);
        }

        // Filtro por estado activo/inactivo
        if ($activeFilter !== null) {
            $query->where('active', $activeFilter);
        }

        // Filtro por nombre (búsqueda parcial e insensible a mayúsculas)
        if ($nameFilter) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($nameFilter) . '%']);
        }

        $users = $query->get();

        return datatables()->of($users)->make(true);
    }
}
