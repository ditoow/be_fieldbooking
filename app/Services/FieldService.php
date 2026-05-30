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

        $query->withMin(['schedules as price_min' => function ($q) {
            $q->where('status', 'available');
        }], 'price');

        $query->withMax(['schedules as price_max' => function ($q) {
            $q->where('status', 'available');
        }], 'price');

        $query->when($filters['category'] ?? null, function ($q, $category){
            return $q->where('category', $category);
        });

        $query->when($filters['status'] ?? null, function ($q, $status){
            return $q->where('status', $status);
        });
        
        return $query->get();
    }

    public function getFieldById($id){
        return Field::with(['schedules' => function ($q) {
                $q->where('status', 'available')->orderBy('start_time', 'asc');
            }])
            ->withMin(['schedules as price_min' => function ($q) {
                $q->where('status', 'available');
            }], 'price')
            ->withMax(['schedules as price_max' => function ($q) {
                $q->where('status', 'available');
            }], 'price')
            ->findOrFail($id);
    }

}
