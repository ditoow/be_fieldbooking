<?php

namespace App\Services;
use App\Models\Field;

class FieldService
{

    //get data

    public function createField($data){
        $field = Field::create($data);
        return $field;
    }

    public function getAllFields($filters = [])
    {
        $query = Field::query();

        $query ->when($filters['category'] ?? null, function ($q, $category){
            return $q->where('category', $category);
        });

        $query ->when($filters['status'] ?? null, function ($q, $status){
            return $q->where('status', $status);
        });
        

        return $query->get();
    }

    public function getFieldById($id){
        
        return Field::with('schedules')->findOrFail($id);
    }

}
