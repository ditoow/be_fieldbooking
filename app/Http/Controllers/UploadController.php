<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FieldService;
use App\Services\BookingService;

class UploadController extends Controller
{
    public function __construct(
        protected FieldService   $fieldService,
        protected BookingService $bookingService
    ) {}

    public function uploadFoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $url = $this->fieldService->uploadFoto($request->file('foto'));

        return response()->json(['message' => 'Foto berhasil diupload', 'url' => $url]);
    }

    public function uploadDokumen(Request $request)
    {
        $request->validate([
            'dokumen' => 'required|file|mimes:pdf|max:1024',
        ]);

        $url = $this->bookingService->uploadDokumen($request->file('dokumen'));

        return response()->json(['message' => 'Dokumen berhasil diupload', 'url' => $url]);
    }
}
