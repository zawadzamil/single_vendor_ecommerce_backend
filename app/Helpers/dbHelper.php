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


    public function createDocument($array)
    {

        return $this->model->create($array);
    }

    public function updateDocument($id, $array)
    {
        $doc = $this->model->find($id);
        $doc->update($array);
        return $doc;
    }

    public function deleteDocument($id): void
    {
        $doc = $this->model->find($id);
        $doc->delete();
    }

    public function getDocument($id)
    {
        return $this->model->find($id);
    }

    public function getDocuments($filter, $sort, $order, $page): array
    {
        $per_page = 5;
        $skip = ($page - 1) * $per_page;

        if ($filter == null) {
            $docs = $this->model::orderBy($order, $sort)->skip($skip)->take($per_page)->get();
            $total = $this->model::orderBy($order, $sort)->count();
        } else {
            $docs = $this->model::where($filter)->orderBy($order, $sort)->skip($skip)->take($per_page)->get();
            $total = $this->model::where($filter)->orderBy($order, $sort)->count();
        }
        $limit = ceil($total / $per_page);
        return ["data" => $docs, "total" => $total, "per_page" => $per_page, "limit" => $limit];
    }

    public function idValidate($array, $request): array
    {
        foreach ($array as $key => $value) {
            if ($request->has($key)) {
                $data = $value::find($request->$key);
                if (!$data) {
                    return ['success' => false, 'message' => 'No data found for this ' . $key . '.'];
                }
            }
        }
        return ['success' => true];
    }

    public function findByIdValidate($request): array
    {
        $modelName = class_basename($this->model);
        $id = $request->query('id');
        if (!$id) {
            return ['success' => false, 'message' => $modelName . ' Id required in request query.', 'status' => 400];
        }

        $data = $this->model::find($id);

        if (!$data) {
            return ['success' => false, 'message' => $modelName . ' not found.', 'status' => 404];
        }
        return ['success' => true, 'data' => $data];
    }

    public function getProducts($request): array
    {
        $per_page = isset($request->per_page) ? (int)$request->per_page : 5;
        $page = $request->query('page') ?? 1;
        $sort = $request->query('sort') ?? 'desc';
        $order = $request->query('orderBy') ?? 'created_at';
        $skip = ($page - 1) * $per_page;
        $filter = [];
        $keysToCheck = ['name', 'category_id', 'brand_id', 'gender'];

        foreach ($keysToCheck as $key) {
            if ($request->has($key)) {
                $filter[$key] = $request->input($key);
            }
        }

        $minPrice = $request->query('minPrice') ?? 0;
        $maxPrice = $request->query('maxPrice') ?? 999999999999999;
        $color = $request->query('color') ?? [];
        $size = $request->query('size') ?? [];

        if (count($filter) == 0) {
            if ($request->has('color') || $request->has('size')) {
                $docs = $this->model::orderBy($order, $sort)
                    ->skip($skip)
                    ->whereBetween('price', [$minPrice, $maxPrice])
                    ->whereHas('variant', function ($query) use ($color, $size) {
                        $query->whereJsonContains('color', $color)
                            ->whereJsonContains('size', $size);
                    })
                    ->with('variant')
                    ->take($per_page)->get();

                $total = $this->model::orderBy($order, $sort)
                    ->whereBetween('price', [$minPrice, $maxPrice])
                    ->whereHas('variant', function ($query) use ($color, $size) {
                        $query->whereJsonContains('color', $color)
                            ->whereJsonContains('size', $size);
                    })->count();

            } else {
                $docs = $this->model::orderBy($order, $sort)
                    ->skip($skip)
                    ->whereBetween('price', [$minPrice, $maxPrice])
                    ->with('variant')
                    ->take($per_page)
                    ->get();

                $total = $this->model::orderBy($order, $sort)
                    ->whereBetween('price', [$minPrice, $maxPrice])
                    ->count();
            }


        } else {
            if ($request->has('color') || $request->has('size')) {
                $docs = $this->model::where($filter)->orderBy($order, $sort)
                    ->skip($skip)
                    ->whereBetween('price', [$minPrice, $maxPrice])
                    ->whereHas('variant', function ($query) use ($color, $size) {
                        $query->whereJsonContains('color', $color)
                            ->whereJsonContains('size', $size);
                    })
                    ->with('variant')
                    ->take($per_page)->get();

                $total = $this->model::where($filter)->orderBy($order, $sort)
                    ->whereBetween('price', [$minPrice, $maxPrice])
                    ->whereHas('variant', function ($query) use ($color, $size) {
                        $query->whereJsonContains('color', $color)
                            ->whereJsonContains('size', $size);
                    })->count();

            } else {
                $docs = $this->model::where($filter)->orderBy($order, $sort)
                    ->skip($skip)
                    ->whereBetween('price', [$minPrice, $maxPrice])
                    ->with('variant')
                    ->with('stock')
                    ->take($per_page)
                    ->get();

                $total = $this->model::where($filter)->orderBy($order, $sort)
                    ->whereBetween('price', [$minPrice, $maxPrice])
                    ->count();
            }
        }

        foreach ($docs as $item){
            if($item->offer){
               if($item->offer->status){
                   $item->offerPrice = round($item->price * ($item->offer['discount'] /100));
               }
            }
        }

        $limit = ceil($total / $per_page);
        return ["data" => $docs, "total" => $total, "per_page" => $per_page, "limit" => $limit];
    }
}
