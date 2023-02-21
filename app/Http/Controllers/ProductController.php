<?php

namespace App\Http\Controllers;

use App\Enums\GenderEnum;
use App\Helpers\dbHelper;
use App\Helpers\FillableChecker;
use App\Helpers\ResponseHelper;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    protected dbHelper $dbHelper;
    protected FillableChecker $fillableChecker;
    protected ResponseHelper $responseHelper;

    public function __construct()
    {

        $this->dbHelper = new dbHelper(new Product());
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $fillableChecker = $this->fillableChecker->check([
            'name',
            'price',
            'slug'
        ], $request);
        if(!$fillableChecker['success']){
            return $this->responseHelper->error($fillableChecker['message'],400);
        }

        try {
            $product = $this->dbHelper->createDocument([
                'name' => $request->name,
                'price' => $request->price,
                'slug' => $request->slug,
                'description' => $request->description ?? null,
                'category_id' => $request->category_id ?? null,
                'brand_id' => $request->brand_id ?? null,
                'gender' => $request->gender ?? null,
                'created_by'=>Auth::user()->id

            ]);
            return $this->responseHelper->created($product,'Product');
        }
        catch (\Exception $e) {
            return $this->responseHelper->error($e->errorInfo[2] ?? $e->getMessage(),400);

        }

    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
