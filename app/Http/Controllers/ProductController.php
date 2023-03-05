<?php

namespace App\Http\Controllers;

use App\Enums\GenderEnum;
use App\Helpers\dbHelper;
use App\Helpers\FillableChecker;
use App\Helpers\ResponseHelper;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Image;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    protected dbHelper $dbHelper;
    protected FillableChecker $fillableChecker;
    protected ResponseHelper $responseHelper;

    public function __construct()
    {

        $this->dbHelper = new dbHelper(new Product());
        $this->fillableChecker = new FillableChecker(new Product());
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
    // FIXME: NEED DATABASE TRANSACTION
    public function store(Request $request): JsonResponse
    {
        $fillableChecker = $this->fillableChecker->check([
            'name',
            'price',
            'slug',
            'quantity'
        ], $request);
        if (!$fillableChecker['success']) {
            return $this->responseHelper->error($fillableChecker['message'], 400);
        }

        // Validate Relational Ids
        $idMap = [
            'category_id' => Category::class,
            'brand_id' => Brand::class,
            'offer_id' => Offer::class,
        ];

        $idValidationResult = $this->dbHelper->idValidate($idMap, $request);
        if (!$idValidationResult['success']) {
            return $this->responseHelper->error($idValidationResult['message'], 404);
        }
        // Color Validation
        if ($request->has('color')) {
            $color = $request->color;
            foreach ($color as $item) {
                if (!in_array($item, ALLOWED_COLORS)) {
                    return response()->json(['message' => 'Invalid color ' . $item], 400);
                }
            }
        }
        // Sizes Validations
        if ($request->has('size')) {
            $sizes = $request->size;
            foreach ($sizes as $item) {
                if (!in_array($item, ALLOWED_SIZES)) {
                    return response()->json(['message' => 'Invalid size ' . $item], 400);
                }
            }
        }


        try {

            // Create Product
            $product = $this->dbHelper->createDocument([
                'name' => $request->name,
                'price' => $request->price,
                'slug' => $request->slug,
                'description' => $request->description ?? null,
                'category_id' => $request->category_id ?? null,
                'brand_id' => $request->brand_id ?? null,
                'gender' => $request->gender ?? null,
                'created_by' => Auth::user()->id

            ]);

            if ($product->save()) {
                // Image Upload
                if ($request->has('images')) {
                    $files = $request->file('images');
                    $primary = true;
                    foreach ($files as $item) {
                        $filename = rand() . '.' . $item->getClientOriginalExtension();
                        Storage::disk('public')->putFileAs('images/products', $item, $filename);
                        $url = Storage::url('images/products/' . $filename);
                        $image = new Image([
                            'url' => $url,
                            'isPrimary' => $primary,
                            'product_id' => $product->id
                        ]);
                        $primary = false;
                        $image->save();
                    }
                }
                // Add Variant
                $varientData = $request->only('color', 'size');

                $varient = tap(ProductVariation::create([
                    'product_id' => $product->id,
                    'color' => $varientData['color'] ?? null,
                    'size' => $varientData['size'] ?? null,
                    'created_by' => Auth::user()->id
                ]), function ($varient) {
                    $varient->makeHidden(['product_id', 'id']);
                });

                $product->varients = $varient;

                // Add Stock
                $productStock = new ProductStock([
                    'quantity' => $request->quantity,
                ]);
                $product->stock()->save($productStock);
            }
            $images = $product->image;
            return $this->responseHelper->created($product, 'Product');

        } catch (\Exception $e) {
            return $this->responseHelper->error($e->errorInfo[2] ?? $e->getMessage(), 400);

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
        $idValidate = $this->dbHelper->findByIdValidate($request);

        if (!$idValidate['success']) {
            return $this->responseHelper->error($idValidate['message'], $idValidate['status']);
        }
        $product = $idValidate['data'];

        if ($product['category_id']) {
            $category = Category::find($product['category_id']);
            $product->categoryName = $category->name;
        }
        if ($product['brand_id']) {
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
     * @param Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $id = $request->query('id');
        if (!$id) {
            return $this->responseHelper->error(config('Product ' . 'messages.id_required'), 400);
        }

        $product = $this->dbHelper->getDocument($id);
        if (!$product) {
            return $this->responseHelper->error('Product ' . config('messages.not_found'), 404);
        }

        // Color Validation
        if ($request->has('color')) {
            $color = $request->color;
            foreach ($color as $item) {
                if (!in_array($item, ALLOWED_COLORS)) {
                    return response()->json(['message' => 'Invalid color ' . $item], 400);
                }
            }
        }

        // Sizes Validation
        if ($request->has('size')) {
            $sizes = $request->size;
            foreach ($sizes as $item) {
                if (!in_array($item, ALLOWED_SIZES)) {
                    return response()->json(['message' => 'Invalid size ' . $item], 400);
                }
            }
        }

        $idMap = [
            'category_id' => Category::class,
            'brand_id' => Brand::class,
            'offer_id' => Offer::class,
        ];
        $idValidationResult = $this->dbHelper->idValidate($idMap, $request);
        if (!$idValidationResult['success']) {
            return $this->responseHelper->error($idValidationResult['message'], 404);
        }


        try {
            $product = $this->dbHelper->updateDocument($id, [
                "name" => $request->name ?? $product->name,
                "price" => $request->price ?? $product->price,
                "slug" => $request->slug ?? $product->slug,
                "description" => $request->description ?? $product->description,
                "category_id" => $request->category_id ?? $product->category_id,
                "brand_id" => $request->brand_id ?? $product->brand_id,
                "offer_id" => $request->offer_id ?? $product->offer_id,
                "gender" => $request->gender ?? $product->gender,
            ]);
            if ($product->save()) {
                if ($request->has('images')) {
                    $files = $request->file('images');
                    foreach ($files as $item) {
                        $filename = rand() . '.' . $item->getClientOriginalExtension();
                        Storage::disk('public')->putFileAs('images/products', $item, $filename);
                        $url = Storage::url('images/products/' . $filename);
                        $image = new Image([
                            'url' => $url,
                            'isPrimary' => false,
                            'product_id' => $product->id
                        ]);
                        $image->save();
                    }
                    $images = $product->image;
                }
                $varientData = $request->only('color', 'size');

                $varient = $product->variant;

                $varient->update([
                    'color' => $varientData['color'] ?? $varient['color'],
                    'size' => $varientData['size'] ?? $varient['size'],
                ]);
                $varient->makeHidden(['product_id', 'id']);

                //                $product->varients = $varient;
                $stock = $product->stock();
                $stock->update([
                    'quantity' => $request->quantity ?? $product->stock()->quantity,
                ]);
            }
            return $this->responseHelper->updated($product, 'Product');
        } catch (\Exception $e) {
            return $this->responseHelper->error($e->errorInfo[2] ?? $e->getMessage(), 400);

        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {
        $id = $request->query('id');
        if (!$id) {
            return $this->responseHelper->error(config('Product ' . 'messages.id_required'), 400);
        }

        $product = $this->dbHelper->getDocument($id);
        if (!$product) {
            return $this->responseHelper->error('Product ' . config('messages.not_found'), 404);
        }
        $this->dbHelper->deleteDocument($id);
        return $this->responseHelper->success('Product ' . config('messages.deleted'));
    }

    /**
     * Get List of products.
     *
     * @param Request $request
     * @return JsonResponse
     */

    //Get All products
    public function all(Request $request): JsonResponse
    {
        try {
            $products = $this->dbHelper->getProducts($request);
            if ($products['total'] == 0) {
                return $this->responseHelper->error('Products ' . config('messages.not_found'), 404);
            }
            return response()->json($products);
        } catch (\Exception $e) {
            return $this->responseHelper->error($e->getMessage(), 400);
        }
    }
}
