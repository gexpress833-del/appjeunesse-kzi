<?php

return [

    /*
    |------------------------------------------------------------------
    | URL de connexion Cloudinary (cloudinary://key:secret@cloud_name)
    |------------------------------------------------------------------
    */
    'cloud_url' => env('CLOUDINARY_URL'),

    /*
    |------------------------------------------------------------------
    | Dossier racine des médias de l'application
    |------------------------------------------------------------------
    */
    'folder' => env('CLOUDINARY_FOLDER', 'appjeune-kzi'),

];
