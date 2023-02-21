<?php

namespace App\Http\Controllers;

use App\Helpers\dbHelper;
use App\Helpers\FillableChecker;
use App\Helpers\ResponseHelper;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected dbHelper $dbHelper;
    protected FillableChecker $fillableChecker;
    protected ResponseHelper $responseHelper;

    public function __construct()
    {

        $this->dbHelper = new dbHelper(new Category());
        $this->fillableChecker = new FillableChecker(new User());
        $this->responseHelper = new ResponseHelper();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $fillables = $this->fillableChecker->check(["name","slug"],$request);

        if(!$fillables['success']){
            return $this->responseHelper->error($fillables['message'],400);
        }

        try {
            if($request->has('parent_id')){
                $parent = $this->dbHelper->getDocument($request->parent_id);
                if(!$parent){
                    return $this->responseHelper->error(config('messages.parent_category_error'),404);
                }
            }
            $category = $this->dbHelper->createDocument([
                'name' => $request->name,
                'slug' => $request->slug,
                'description' => $request->description ?? null,
                'parent_id' => $request->parent_id ?? null,
                'created_by'=> auth()->user()->id,
            ]);
            return $this->responseHelper->created($category,'Category');
        }
        catch (\Exception $e){
            return $this->responseHelper->error($e->errorInfo[2] ?? $e->getMessage(),403);
        }


    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $id = $request->query('id');
        if(!$id){
            return $this->responseHelper->error('Category '.config('messages.id_required'),400);
        }

        $category = $this->dbHelper->getDocument($id);
        if(!$category){
            return $this->responseHelper->error('Category '.config('messages.not_found'),404);
        }

        return $this->responseHelper->success($category);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param  \App\Models\Category  $category
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $id = $request->query('id');
        if(!$id){
            return $this->responseHelper->error('Category '.config('messages.id_required'),400);
        }

        $category = $this->dbHelper->getDocument($id);
        if(!$category){
            return $this->responseHelper->error('Category '.config('messages.not_found'),404);
        }

        if($request->has('parent_id')){
            $parent = $this->dbHelper->getDocument($request->parent_id);
            if(!$parent){
                return $this->responseHelper->error(config('messages.parent_category_error'),404);
            }
            if($parent->id === $category->id){
                return $this->responseHelper->error(config('messages.parent_category_same'),400);
            }
        }

        $doc = $this->dbHelper->updateDocument($id,[
            'name' => $request->name ?? $category->name,
           'slug' => $request->slug ?? $category->slug,
            'description' => $request->description?? $category->description,
            'parent_id' => $request->parent_id?? $category->parent_id,
        ]);

        return $this->responseHelper->updated($doc,'Category');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Category  $category
     * @return JsonResponse
     */
    public function destroy(Request $request)
    {
        $id = $request->query('id');
        if(!$id){
            return $this->responseHelper->error('Category '.config('messages.id_required'),400);
        }

        $category = $this->dbHelper->getDocument($id);
        if(!$category){
            return $this->responseHelper->error('Category '.config('messages.not_found'),404);
        }

        $hasChildren = $category->children()->exists();
        if($hasChildren){
            return $this->responseHelper->error(config('messages.cant_delete_category'),400);
        }

        $this->dbHelper->deleteDocument($id);

        return $this->responseHelper->success('Category '.config('messages.deleted'));
    }

    /**
     * Display All Categories.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function all(Request $request): JsonResponse
    {
        $categories = Category::tree();
        return $this->responseHelper->success($categories);
    }
}
