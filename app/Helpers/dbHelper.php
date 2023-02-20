<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;

class dbHelper
{
    protected Model $model;
    public function __construct($model)
    {
        $this->model = $model;
    }


    public function createDocument($array){

        return $this->model->create($array);
    }

    public function updateDocument($id, $array){
        $doc = $this->model->find($id);
        $doc->update($array);
        return $doc;
    }

    public function deleteDocument($id){
        $doc = $this->model->find($id);
        $doc->delete();
    }

    public function getDocument($id){
        return $this->model->find($id);
    }

    public function getDocuments($filter,$sort,$order,$page): array
    {
        $per_page = 5;
        $skip = ($page - 1) * $per_page;

       if($filter == null){
           $docs = $this->model::orderBy($order, $sort)->skip($skip)->take($per_page)->get();
           $total = $this->model::orderBy($order, $sort)->count();
       }
       else{
           $docs = $this->model::where($filter)->orderBy($order, $sort)->skip($skip)->take($per_page)->get();
           $total = $this->model::where($filter)->orderBy($order, $sort)->count();
       }
        $limit = ceil($total / $per_page);
        return ["data"=>$docs,"total" => $total, "per_page" => $per_page,"limit"=>$limit];
    }
}
