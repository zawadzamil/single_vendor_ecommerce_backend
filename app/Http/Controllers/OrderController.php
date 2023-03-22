<?php

namespace App\Http\Controllers;

use App\Helpers\dbHelper;
use App\Helpers\FillableChecker;
use App\Helpers\ResponseHelper;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected dbHelper $dbHelper;
    protected FillableChecker $fillableChecker;
    protected ResponseHelper $responseHelper;

    public function __construct()
    {

        $this->dbHelper = new dbHelper(new Order());
        $this->fillableChecker = new FillableChecker(new Order());
        $this->responseHelper = new ResponseHelper();
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
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
    public function store(Request $request): JsonResponse
    {
        $fillables = ['total_amount', 'order_date', 'delivery_address'];
        $fillacleCheck = $this->fillableChecker->check($fillables, $request);
        if (!$fillacleCheck['success']) {
            return $this->responseHelper->error($fillacleCheck['message'], 400);
        }
        $user = Auth::user();
        $customer = $user->customer;

        // Get the customer cart
        $cart = Cart::where('customer_id', $customer->id)->first();
        if(!$cart) {
            return $this->responseHelper->error(config('messages.emptyCart'),400);
        }

        // Cart Items
        $cartItems = $cart->items;


        try {
            $order = $this->dbHelper->createDocument([
                'customer_id' => $customer->id ,
                'total_amount' => (int)$request->input('total_amount'),
                'order_date' => $request->input('order_date'),
                'delivery_address' => $request->input('delivery_address'),
            ]);
            if($order->save()) {
                foreach($cartItems as $cartItem) {
                    $orderItem = OrderItem::create([
                        'order_id'=>$order->id,
                        'product_id' => $cartItem->product_id,
                        'quantity' =>$cartItem->quantity,
                        'unit_price' => $cartItem->price
                    ]);
                }
            }
            return $this->responseHelper->created($order,'Order');
        }
        catch (\Exception $e){
            return $this->responseHelper->error($e->getMessage(), 400);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param Order $order
     * @return Response
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Order $order
     * @return Response
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Order $order
     * @return Response
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Order $order
     * @return Response
     */
    public function destroy(Order $order)
    {
        //
    }
}
