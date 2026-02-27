<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class JournalImageController extends Controller
{
    // ✅ DESPUÉS — Subcarpeta por usuario
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            // ↑ Añadimos mimes explícito: 'image' solo valida el mimetype del header,
            //   que puede ser falseado. mimes valida la extensión real del archivo.
        ]);

        if ($request->hasFile('file')) {
            // Subcarpeta por user_id: journal-attachments/42/nombrearchivo.jpg
            // Ventajas:
            // 1. Saber de quién es cada imagen sin query adicional
            // 2. Limpiar imágenes de un usuario al eliminar su cuenta (cascadeOnDelete visual)
            // 3. Evitar colisiones de nombres entre usuarios
            $path = $request->file('file')->store(
                'journal-attachments/' . Auth::id(),
                'public'
            );

            return response()->json([
                'url' => Storage::url($path)
            ], 200);
        }

        return response()->json(['error' => __('labels.no_file_uploaded')], 400);
    }


    // Opcional: Para borrar imágenes si el usuario las quita del editor (Limpieza)
    public function destroy(Request $request)
    {
        // Lógica avanzada para borrar archivos si quieres implementarla luego
        return response()->json(['status' => 'ok']);
    }
}
