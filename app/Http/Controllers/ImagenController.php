<?php

namespace App\Http\Controllers;

use App\Models\Imagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Producto;
use Illuminate\Support\Facades\Response;

class ImagenController extends Controller
{
    public function destroy(Request $request, Imagen $imagen)
    {
        try {
            // Configuración de Cloudinary
            $cloudName = config('cloudinary.cloud.cloud_name');
            $apiKey = config('cloudinary.cloud.api_key');
            $apiSecret = config('cloudinary.cloud.api_secret');

            $timestamp = time();
            $publicId = $imagen->imagen_public_id;
            // Corregir la firma: reemplazar ×tamp por &timestamp
            $paramsToSign = "public_id={$publicId}&timestamp={$timestamp}{$apiSecret}";
            $signature = hash('sha256', $paramsToSign);

            // Eliminar de Cloudinary
            $response = Http::asForm()->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy", [
                'public_id' => $publicId,
                'api_key' => $apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
            ]);

            if (!$response->successful()) {
                return response()->json(['error' => 'Error al eliminar la imagen de Cloudinary'], 500);
            }

            // Eliminar de la base de datos
            $imagen->delete();

            // Devolver respuesta JSON para AJAX
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error inesperado al eliminar la imagen'], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $producto = Producto::findOrFail($request->producto_id);

        if ($producto->imagenes()->count() >= 5) {
            if ($request->ajax()) {
                return response()->json(['error' => 'No podés subir más de 5 imágenes.'], 422);
            }
            return back()->withErrors(['imagen' => 'No podés subir más de 5 imágenes para este producto.']);
        }

        // Cloudinary setup
        $uploadedFile = $request->file('imagen');
        $slugNombre = Str::slug($producto->nombre);
        $timestamp = time();
        $index = $producto->imagenes()->count();
        $cloudName = config('cloudinary.cloud.cloud_name');
        $apiKey = config('cloudinary.cloud.api_key');
        $apiSecret = config('cloudinary.cloud.api_secret');
        $folder = 'productos';
        $publicId = "{$slugNombre}_{$producto->id}_{$index}_{$timestamp}";

        // Corregir la cadena de firma
        $params_to_sign = "folder={$folder}&public_id={$publicId}&timestamp={$timestamp}{$apiSecret}";
        $signature = hash('sha256', $params_to_sign);

        $response = Http::asMultipart()->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
            [
                'name'     => 'file',
                'contents' => fopen($uploadedFile->getRealPath(), 'r'),
                'filename' => $uploadedFile->getClientOriginalName(),
            ],
            ['name' => 'api_key', 'contents' => $apiKey],
            ['name' => 'timestamp', 'contents' => $timestamp],
            ['name' => 'folder', 'contents' => $folder],
            ['name' => 'public_id', 'contents' => $publicId],
            ['name' => 'signature', 'contents' => $signature],
        ]);

        if (!$response->successful()) {
            return response()->json(['error' => 'Error al subir la imagen a Cloudinary.'], 500);
        }

        $result = $response->json();

        if (!isset($result['secure_url']) || !isset($result['public_id'])) {
            return response()->json(['error' => 'Respuesta inválida de Cloudinary.'], 500);
        }

        $imagen = $producto->imagenes()->create([
            'imagen_url' => $result['secure_url'],
            'imagen_public_id' => $result['public_id'],
        ]);

        return response()->json([
            'success' => true,
            'imagen_url' => $imagen->imagen_url,
            'imagen_id' => $imagen->id,
            'public_id' => $imagen->imagen_public_id,
            'producto_id' => $producto->id
        ]);
    }
}
