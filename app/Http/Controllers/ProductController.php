<?php

namespace App\Http\Controllers;

use App\Enums\GenderEnum;
use App\Helpers\dbHelper;
use App\Helpers\FillableChecker;
use App\Helpers\ResponseHelper;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
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

//        if($request->has('category_id')){
//            $category = Category::find($request->category_id);
//            if(!$category){
//                return $this->responseHelper->error('Invalid category',400);
//            }
//        }
        // Ids Validation
        $idMap = [
            'category_id' => Category::class,
            'brand_id' => Brand::class,
            'offer_id'=> Offer::class,
        ];

        $idValidationResult = $this->dbHelper->idValidate($idMap, $request);
        if(!$idValidationResult['success']){
            return $this->responseHelper->error($idValidationResult['message'], 404);
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request): \Illuminate\Http\JsonResponse
    {
       $idValidate = $this->dbHelper->findByIdValidate($request);

       if(!$idValidate['success']){
           return $this->responseHelper->error($idValidate['message'], $idValidate['status']);
       }
      $product = $idValidate['data'];

       if($product['category_id']){
           $category = Category::find($product['category_id']);
           $product->categoryName = $category->name;
       }
       if($product['brand_id']){
           $brand = Brand::find($product['brand_id']);
           $product->brandName = $brand->name;
       }

//       if($product['offer_id']){
//           $offer = Offer::find($product['offer_id']);
//           $product->offerName = $offer->name;
//       }

        return $this->responseHelper->success($product);
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $id = $request->query('id');
        if (!$id) {
            return $this->responseHelper->error(config('Product '.'messages.id_required'),400);
        }

        $product = $this->dbHelper->getDocument($id);
        if(!$product) {
            return $this->responseHelper->error('Product '.config('messages.not_found'),404);
        }

        $idMap = [
            'category_id' => Category::class,
            'brand_id' => Brand::class,
            'offer_id'=> Offer::class,
        ];
        $idValidationResult = $this->dbHelper->idValidate($idMap, $request);
        if(!$idValidationResult['success']){
            return $this->responseHelper->error($idValidationResult['message'], 404);
        }

        try {
            $product = $this->dbHelper->updateDocument($id,[
                "name" => $request->name ?? $product->name,
                "price" => $request->price?? $product->price,
                "slug" => $request->slug?? $product->slug,
                "description" => $request->description?? $product->description,
                "category_id" => $request->category_id?? $product->category_id,
                "brand_id" => $request->brand_id?? $product->brand_id,
                "offer_id" => $request->offer_id?? $product->offer_id,
                "gender" => $request->gender?? $product->gender,

            ]);
            return $this->responseHelper->updated($product,'Product');
        }
        catch (\Exception $e) {
            return $this->responseHelper->error($e->errorInfo[2]?? $e->getMessage(),400);

        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request): \Illuminate\Http\JsonResponse
    {
        $id = $request->query('id');
        if (!$id) {
            return $this->responseHelper->error(config('Product '.'messages.id_required'),400);
        }

        $product = $this->dbHelper->getDocument($id);
        if(!$product) {
            return $this->responseHelper->error('Product '.config('messages.not_found'),404);
        }
        $this->dbHelper->deleteDocument($id);
        return $this->responseHelper->success('Product '.config('messages.deleted'));
    }

    /**
     * Get List of products.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\JsonResponse
     **/

    //Get All products
    public function all(Request $request){
        try {
            $products = $this->dbHelper->getProducts($request);
            if ($products['total'] == 0) {
                return $this->responseHelper->error('Products ' . config('messages.not_found'), 404);
            }
            return response()->json($products);
        }
        catch (\Exception $e) {
            return $this->responseHelper->error($e->getMessage(),400);
        }
    }
}
