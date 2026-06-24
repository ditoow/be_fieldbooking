<?php

return [
    'url'             => env('SUPABASE_URL'),
    'key'             => env('SUPABASE_KEY'),
    'bucket_image'    => env('SUPABASE_BUCKET_IMAGE', 'Field-Image'),
    'bucket_document' => env('SUPABASE_BUCKET_DOCUMENT', 'File-Document'),
    'bucket_report'   => env('SUPABASE_BUCKET_REPORT', 'reports'),
];
