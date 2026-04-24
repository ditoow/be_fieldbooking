<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Field;

class FieldController extends Controller
{

   
    public function index(){
        $fields = Field::all();
        return response()->json([
            'success' => true,
            'message' => $fields,
        ]);
    }

   
    public function store(Request $request){
        $request->validate([
            'nama_lapangan' => 'required',
            'deskripsi' => 'required',
            'kategori_lapangan' => 'required',
            'status' => 'required|in:tersedia,pemeliharaan',
        ]);

        $fields = Field::create([
            'nama_lapangan' => $request->nama_lapangan,
            'deskripsi' => $request->deskripsi,
            'kategori_lapangan' => $request->kategori_lapangan,
            'status' => $request->status ?? 'tersedia',
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Field created successfully',
            'data' => $fields,
        ], 201);
    }
}
