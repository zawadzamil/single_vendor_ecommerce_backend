<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;

class FillableChecker
{
    protected Model $model;
    public function __construct($model)
    {
        $this->model = $model;
    }


    public function check($fillableFields,$request)
    {
        $missingFields = array_filter($fillableFields, function ($field) use ($request) {
            return empty($request->input($field));
        });

        if (count($missingFields) > 0) {
            // Some fillable fields are missing
            $missingFieldsMessage = "The following field(s) is / are missing: " . implode(", ", $missingFields);
           return ['success'=>false, 'message'=>$missingFieldsMessage];
        }
        return ['success'=>true];
    }



}
