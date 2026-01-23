<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class JournalImageController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validar que sea una imagen real
        $request->validate([
            'file' => 'required|image|max:5120', // Máx 5MB
        ]);

        if ($request->hasFile('file')) {
            // 2. Guardar en disco 'public' dentro de la carpeta 'journal-attachments'
            $path = $request->file('file')->store('journal-attachments', 'public');

            // 3. Devolver la URL pública para que Trix la muestre
            return response()->json([
                'url' => Storage::url($path)
            ], 200);
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }

    // Opcional: Para borrar imágenes si el usuario las quita del editor (Limpieza)
    public function destroy(Request $request)
    {
        // Lógica avanzada para borrar archivos si quieres implementarla luego
        return response()->json(['status' => 'ok']);
    }
}
