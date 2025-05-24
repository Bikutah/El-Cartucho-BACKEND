<?php

namespace App\Http\Controllers;

use App\Models\Imagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ImagenController extends Controller
{
    public function destroy(Imagen $imagen)
    {
        // Eliminar de Cloudinary (opcional, si querés liberar espacio)
        $cloudName = config('cloudinary.cloud.cloud_name');
        $apiKey = config('cloudinary.cloud.api_key');
        $apiSecret = config('cloudinary.cloud.api_secret');

        $timestamp = time();
        $publicId = $imagen->imagen_public_id;
        $signature = hash('sha256', "public_id=$publicId&timestamp=$timestamp$apiSecret");

        $response = Http::asForm()->post("https://api.cloudinary.com/v1_1/$cloudName/image/destroy", [
            'public_id' => $publicId,
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);

        // Eliminar del sistema
        $imagen->delete();

        return back()->with('success', 'Imagen eliminada correctamente');
    }
}
