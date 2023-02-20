<?php

namespace App\Http\Controllers;

use App\Helpers\dbHelper;
use App\Helpers\FillableChecker;
use App\Helpers\ResponseHelper;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    protected dbHelper $dbHelper;
    protected FillableChecker $fillableChecker;
    protected ResponseHelper $responseHelper;

    public function __construct()
    {

        $this->dbHelper = new dbHelper(new Customer());
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
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        // Validate Required Fields
        $filled = $this->fillableChecker->check(['name', 'email', 'password', 'username', 'address'], $request);
        if (!$filled['success']) {
            return response()->json(['message' => $filled['message']], 422);
        }

        $username = str_replace(' ', '', $request->input('username'));
        $existingUser = User::where('username', $username)->get();

        if (count($existingUser) > 0) {
            return response()->json(['message' => 'Username already exists'], 409);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $username,
                'password' => Hash::make($request->password),
            ]);

            if ($user->save()) {
                $customer = $this->dbHelper->createDocument([
                    'name' => $request->name,
                    'address' => $request->address,
                    'user_id' => $user->id,
                ]);
                return $this->responseHelper->created($customer, 'Customer');
            } else {
                return $this->responseHelper->error(null, null);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->errorInfo[2] ?? 'Something went wrong!'], 400);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
        if (!$id) {
            return $this->responseHelper->error(config('messages.id_required'), 400);
        }
        // Find the customer
        $customer = $this->dbHelper->getDocument($id);
        $user = $customer->user;
        if (!$customer) {
            return $this->responseHelper->error(config('messages.customer_not_found'), 404);
        }

        return $this->responseHelper->success($customer);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Customer $customer
     * @return \Illuminate\Http\Response
     */
    public function edit(Customer $customer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Customer $customer
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        $id = $request->query('id');
        if (!$id) {
            return $this->responseHelper->error(config('messages.id_required'), 400);
        }
        // Find the customer
        $customer = $this->dbHelper->getDocument($id);
        if (!$customer) {
            return $this->responseHelper->error(config('messages.customer_not_found'), 404);
        }
        //Update the customer
        $toUpdate = [
            'name' => $request->name ?? $customer->name,
            'address' => $request->address ?? $customer->address
        ];

        $doc = $this->dbHelper->updateDocument($id, $toUpdate);

        return $this->responseHelper->success($doc);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Customer $customer
     * @return JsonResponse
     */
    public function destroy(Request $request)
    {
        $id = $request->query('id');
        if (!$id) {
            return $this->responseHelper->error(config('messages.id_required'), 400);
        }
        // Find the customer
        $customer = $this->dbHelper->getDocument($id);
        if (!$customer) {
            return $this->responseHelper->error(config('messages.customer_not_found'), 404);
        }
        $user = $customer->user;
        $user->delete();
        $customer->delete();
        return $this->responseHelper->successWithMessage(config('messages.customer_deleted'));
    }
}
