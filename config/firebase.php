<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | El ID del proyecto de Firebase. Se usa para validar los claims "iss" y
    | "aud" de los ID tokens emitidos por Firebase Authentication.
    |
    | Se obtiene en la Consola de Firebase → Configuración del proyecto → General.
    |
    */
    'project_id' => env('FIREBASE_PROJECT_ID'),

    /*
    |--------------------------------------------------------------------------
    | URL de claves públicas de Google
    |--------------------------------------------------------------------------
    |
    | Google expone los certificados X.509 de Firebase en esta URL.
    | El TTL del caché se toma del header Cache-Control de la respuesta.
    |
    */
    'keys_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com',
];
