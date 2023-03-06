<?php

namespace App\Http\Controllers;

use App\Helpers\dbHelper;
use App\Helpers\FillableChecker;
use App\Helpers\ResponseHelper;
use App\Models\Offer;
use App\Models\Product;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfferController extends Controller
{
    protected dbHelper $dbHelper;
    protected FillableChecker $fillableChecker;
    protected ResponseHelper $responseHelper;

    public function __construct()
    {

        $this->dbHelper = new dbHelper(new Offer());
        $this->fillableChecker = new FillableChecker(new Offer());
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
    public function store(Request $request): JsonResponse
    {
        $fillables = ['name', 'discount', 'startDate', 'endDate'];
        $fillableCheck = $this->fillableChecker->check($fillables, $request);
        if (!$fillableCheck['success']) {
            return $this->responseHelper->error($fillableCheck['message'], 400);
        }

        try {
            $offer = $this->dbHelper->createDocument([
                'name' => $request->input('name'),
                'description' => $request->input('description') ?? null,
                'discount' => (int)$request->input('discount'),
                'start_date' => $request->input('startDate'),
                'end_date' => $request->input('endDate'),
                'created_by' => Auth::user()->id,
            ]);

            return $this->responseHelper->created($offer, 'Offer');
        } catch (Exception $e) {
            return $this->responseHelper->error($e->errorInfo[2] ?? $e->getMessage(), 400);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Offer $offer
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $idValidate = $this->dbHelper->findByIdValidate($request);

        if (!$idValidate['success']) {
            return $this->responseHelper->error($idValidate['message'], $idValidate['status']);
        }

        $offer = $idValidate['data'];

        return $this->responseHelper->success($offer);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Offer $offer
     * @return \Illuminate\Http\Response
     */
    public function edit(Offer $offer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param \App\Models\Offer $offer
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $idValidate = $this->dbHelper->findByIdValidate($request);

        if (!$idValidate['success']) {
            return $this->responseHelper->error($idValidate['message'], $idValidate['status']);
        }

        $offer = $idValidate['data'];
        try {
            $offer->update([
                'name' => $request->input('name') ?? $offer->name,
                'description' => $request->input('description') ?? $offer->description,
                'discount' => (int)$request->input('discount') ?? $offer->discount,
                'start_date' => $request->input('startDate') ?? $offer->start_date,
                'end_date' => $request->input('endDate') ?? $offer->end_date
            ]);

            return $this->responseHelper->updated($offer, 'Offer');
        } catch (Exception $e) {
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
        $idValidate = $this->dbHelper->findByIdValidate($request);

        if (!$idValidate['success']) {
            return $this->responseHelper->error($idValidate['message'], $idValidate['status']);
        }

        $this->dbHelper->deleteDocument($request->query('id'));

        return $this->responseHelper->successWithMessage('Offer' . config('messages.deleted'));
    }

    // Active offer
    public function activateOrDeactivate(Request $request): JsonResponse
    {
        $idValidate = $this->dbHelper->findByIdValidate($request);

        if (!$idValidate['success']) {
            return $this->responseHelper->error($idValidate['message'], $idValidate['status']);
        }
        $offer = $idValidate['data'];

        if (!$request->has('active')) {
            return $this->responseHelper->error(config('messages.activeOrDeactivate'), 400);
        }

        $active = $request->query('active');

        $offer->update([
            'status' => $active ? 1 : 0,
        ]);

        return $this->responseHelper->successWithMessageAndData('Operation Successful.', $offer);
    }

    public function all(Request $request)
    {
        $per_page = isset($request->per_page) ? (int)$request->per_page : 5;
        $page = $request->query('page') ?? 1;
        $sort = $request->query('sort') ?? 'desc';
        $order = $request->query('orderBy') ?? 'created_at';
        $skip = ($page - 1) * $per_page;
        $filter = [];
        $keysToCheck = ['name', 'discount', 'status'];

        foreach ($keysToCheck as $key) {
            if ($request->has($key)) {
                $filter[$key] = $request->input($key);
            }
        }

        if (count($filter) == 0) {
            $offers = Offer::orderBy($order,$sort)->skip($skip)->take($per_page)->get();
            $total = Offer::all()->count();
        } else {
            $offers = Offer::where($filter)->orderBy($order,$sort)->skip($skip)->take($per_page)->get();
            $total = Offer::where($filter)->count();
        }

        if (count($offers) < 1) {
            return $this->responseHelper->error(config('messages.noOffers'), 404);
        }

        $limit = ceil($total / $per_page);
        return ["data" => $offers, "total" => $total, "per_page" => $per_page, "limit" => $limit];
    }

    // Active Offers
    public function allActive(Request $request){
        $per_page = isset($request->per_page) ? (int)$request->per_page : 5;
        $page = $request->query('page') ?? 1;
        $sort = $request->query('sort') ?? 'desc';
        $order = $request->query('orderBy') ?? 'created_at';
        $skip = ($page - 1) * $per_page;
        $filter = [
            'status' => 1
        ];

        $offers = Offer::where($filter)->orderBy($order,$sort)->skip($skip)->take($per_page)->get();
        $total = Offer::where($filter)->count();
        if (count($offers) < 1) {
            return $this->responseHelper->error(config('messages.noOffers'), 404);
        }

        $limit = ceil($total / $per_page);
        return ["data" => $offers, "total" => $total, "per_page" => $per_page, "limit" => $limit];
    }
}
