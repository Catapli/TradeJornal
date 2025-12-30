<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RolsController extends Controller
{
    public function data()
    {

        // if (!$request->ajax()) {
        //     abort(403, 'Acceso no autorizado.');
        // }
        try {
            // $townFilter = $request->input('town');
            // $nameFilter = $request->input('name');
            // $userFilter = $request->input('user');

            // Construir la consulta base con relación y campos específicos
            $rols = Role::where('name', '!=', 'superadmin')->get();


            return datatables()->of($rols)->make(true);
        } catch (\Exception $e) {
            Log::error('Error en el proceso: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return datatables()->of([])->make(true);
        }
    }
}
