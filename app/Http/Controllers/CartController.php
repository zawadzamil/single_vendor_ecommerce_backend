<?php

namespace App\Http\Controllers;

use App\Helpers\dbHelper;
use App\Helpers\FillableChecker;
use App\Helpers\ResponseHelper;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CartController extends Controller
{
    protected dbHelper $dbHelper;
    protected FillableChecker $fillableChecker;
    protected ResponseHelper $responseHelper;

    public function __construct()
    {

        $this->dbHelper = new dbHelper(new CartItem());
        $this->fillableChecker = new FillableChecker(new CartItem());
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
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $fillables = ['product_id', 'quantity', 'price'];
        $checkFillables = $this->fillableChecker->check($fillables, $request);
        if (!$checkFillables['success']) {
            return $this->responseHelper->error($checkFillables['message'], 400);
        }

        $product = Product::find($request->product_id);
        if (!$product) {
            return $this->responseHelper->error('Product ' . config('messages.not_found'), 404);
        }

        if ($request->quantity < 1) {
            $this->responseHelper->error(config('messages.lowQuantity'), 400);
        }

        $user = Auth::user();
        $existingCart = Cart::where('customer_id', $user->id)->first();
        if (!$existingCart) {
            try {
                $cart = $this->dbHelper->createDocument([
                    'customer_id' => $user->id,
                    'customer_name' => $user->name,
                ]);
            } catch (\Exception $e) {
                return $this->responseHelper->error($e->errorInfo[2] ?? $e->getMessage(), 400);
            }
        } else {
            $cart = $existingCart;
        }

        try {
            $stock = $product->stock;

            if ($stock->reserve($request->quantity)) {
                // Merge Cart with existing
                $existingCartItem = CartItem::where('cart_id', $cart->id)
                    ->where('product_id', $product->id)
                    ->where('size', $request->size)
                    ->where('color', $request->color)
                    ->first();

                if ($existingCartItem) {
                    $quantity = $existingCartItem['quantity'] + $request['quantity'];
                    $existingCartItem->update([
                        'quantity' => $quantity,
                        'total_price' => $quantity * $existingCartItem->price,
                    ]);
                } else {
                    $cartItem = CartItem::create([
                        'cart_id' => $cart->id,
                        'product_id' => $request->input('product_id'),
                        'size' => $request->input('size') ?? null,
                        'color' => $request->input('color') ?? null,
                        'price' => $request->input('price'),
                        'quantity' => (int)$request->input('quantity'),
                        'total_price' => $request->input('price') * $request->input('quantity'),
                    ]);
                }
                return $this->responseHelper->successWithMessage(config('messages.cartAdded'));
            } else {
                return $this->responseHelper->error(config('messages.lowStock'), 403);
            }
        } catch (Throwable  $e) {
            return $this->responseHelper->error(config('messages.unexpectedError'), 403);
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Cart $cart
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $fillables = ['cartItemId','quantity'];
        $checkFillables = $this->fillableChecker->check($fillables,$request);
        if(!$checkFillables['success']){
            return $this->responseHelper->error($checkFillables['message'],400);
        }

        $cartItem = $this->dbHelper->getDocument($request->cartItemId);
        if(!$cartItem){
            return $this->responseHelper->error('Cart Item '.config('messages.not_found'),404);
        }
        $product = Product::find($cartItem->product_id);
        if(!$product){
            return $this->responseHelper->error(config('messages.productNotFound'),404);
        }

        $oldQuantity = $cartItem->quantity;
        $newQuantity = $request->quantity;

        $quantityDiff = $newQuantity - $oldQuantity;

        if($quantityDiff > 0){
            if($product->reserveStock($quantityDiff)){
                $cartItem->update([
                    'quantity' => $request->quantity ,
                    'total_price' => $cartItem->price * $request->input('quantity'),
                ]);
            }
            else{
                return $this->responseHelper->error(config('messages.lowStock'), 403);
            }
        }
        elseif($quantityDiff < 0){
            $product->unreserveStock(abs($quantityDiff));
            $cartItem->update([
                'quantity' => $request->quantity ,
                'total_price' => $cartItem->price * $request->input('quantity'),
            ]);
        }
       return $this->responseHelper->updated($cartItem,'Cart');


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
     * Display the specified resource.
     *
     * @param Cart $cart
     * @return JsonResponse
     */
    public function show(Cart $cart): JsonResponse
    {
        $user = Auth::user();
        $cart = Cart::where('customer_id', $user->id)->first();
        $cartItems = CartItem::where('cart_id', $cart->id)->get();
        if($cartItems->count() == 0){
            return $this->responseHelper->error(config('messages.emptyCart'), 403);
        }
        $data = ['items'=>$cartItems,'total'=>$cartItems->count()];
        return $this->responseHelper->success($data);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Cart $cart
     * @return \Illuminate\Http\Response
     */
    public function edit(Cart $cart)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Cart $cart
     * @return \Illuminate\Http\Response
     */
    public function destroy(Cart $cart)
    {
        //
    }

    // Remove Item from Cart
    public function remove(Request $request): JsonResponse
    {
       $fillables = ['cartItemId'];
       $checkFillables = $this->fillableChecker->check($fillables,$request);
       if(!$checkFillables['success']){
           return $this->responseHelper->error($checkFillables['message'],400);
       }

       $cartItem = $this->dbHelper->getDocument($request->cartItemId);
       if(!$cartItem){
           return $this->responseHelper->error('Cart Item '.config('messages.not_found'),404);
       }

       $productId = $cartItem->product_id;
       $product = Product::find($productId);
       if(!$product){
           return $this->responseHelper->error(config('messsages.productNotFound'),404);
       }

      try{
          $product->unreserveStock($cartItem->quantity);

          $this->dbHelper->deleteDocument($cartItem->id);

      }
       catch (\Exception $e){
           return $this->responseHelper->error($e->getMessage(),400);
       }

       return $this->responseHelper->successWithMessage('Cart Item '.config('messages.removeCart'));


    }
}
