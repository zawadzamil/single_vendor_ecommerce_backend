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
        $fillables = ['name','amount','startDate','endDate'];
        $fillableCheck = $this->fillableChecker->check($fillables,$request);
        if(!$fillableCheck['success']){
            return $this->responseHelper->error($fillableCheck['message'],400);
        }

        try {
            $offer = $this->dbHelper->createDocument([
                'name'=>$request->input('name'),
                'description'=> $request->input('description') ?? null,
                'amount'=>(int)$request->input('amount'),
                'start_date'=>$request->input('startDate'),
                'end_date'=>$request->input('endDate'),
                'created_by'=>Auth::user()->id,
            ]);

            return $this->responseHelper->created($offer,'Offer');
        }
        catch (Exception $e){
            return $this->responseHelper->error($e->errorInfo[2] ?? $e->getMessage(), 400);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Offer  $offer
     * @return \Illuminate\Http\Response
     */
    public function show(Offer $offer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Offer  $offer
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
     * @param  \App\Models\Offer  $offer
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
                'name'=> $request->input('name') ?? $offer->name,
                'description' => $request->input('description') ?? $offer->description,
                'amount' =>(int) $request->input('amount') ?? $offer->amount,
                'start_date' => $request->input('startDate') ?? $offer->start_date,
                'end_date' => $request->input('endDate') ?? $offer->end_date
            ]);

            return $this->responseHelper->updated($offer,'Offer');
        }
        catch (Exception $e) {
            return $this->responseHelper->error($e->errorInfo[2] ?? $e->getMessage(), 400);
        }


    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Offer  $offer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Offer $offer)
    {
        //
    }
}
