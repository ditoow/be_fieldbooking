<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi ini digunakan untuk mengintegrasikan Cloudinary sebagai
    | penyimpanan cloud untuk file upload mahasiswa (surat persyaratan).
    | Semua kredensial dikonfigurasi via environment variable CLOUDINARY_URL.
    |
    */

    'cloud_url' => env('CLOUDINARY_URL'),

    /*
    |--------------------------------------------------------------------------
    | Upload Folder
    |--------------------------------------------------------------------------
    |
    | Folder default di Cloudinary untuk menyimpan file upload.
    |
    */

    'upload_folder' => env('CLOUDINARY_UPLOAD_FOLDER', 'booking-files'),

];
