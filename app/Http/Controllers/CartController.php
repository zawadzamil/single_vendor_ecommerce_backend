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

        $this->dbHelper = new dbHelper(new Cart());
        $this->fillableChecker = new FillableChecker(new Cart());
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
     * @param \App\Models\Cart $cart
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cart $cart)
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
     * Display the specified resource.
     *
     * @param \App\Models\Cart $cart
     * @return \Illuminate\Http\Response
     */
    public function show(Cart $cart)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Cart $cart
     * @return \Illuminate\Http\Response
     */
    public function edit(Cart $cart)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Cart $cart
     * @return \Illuminate\Http\Response
     */
    public function destroy(Cart $cart)
    {
        //
    }
}
