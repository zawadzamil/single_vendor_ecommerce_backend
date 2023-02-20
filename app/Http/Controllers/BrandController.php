<?php

namespace App\Http\Controllers;

use App\Helpers\dbHelper;
use App\Helpers\FillableChecker;
use App\Helpers\ResponseHelper;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    protected dbHelper $dbHelper;
    protected FillableChecker $fillableChecker;
    protected ResponseHelper $responseHelper;

    public function __construct()
    {

        $this->dbHelper = new dbHelper(new Brand());
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
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $fillables = $this->fillableChecker->check(['name'], $request);

        if (!$fillables['success']) {
            return $this->responseHelper->error($fillables['message'], 400);
        }

        if ($request->has('image')) {
            $file = $request->file('image');
            $filename = rand() . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images/brands', $file, $filename);
        }

        $filename = $filename ?? 'dammy.png';
        $url = Storage::url('images/brands/' . $filename);

        $brand = $this->dbHelper->createDocument([
            "name" => $request->input('name'),
            "description" => $request->input('description') ?? null,
            "image" => $url,
            "created_by" => auth()->user()->id,
        ]);

        return $this->responseHelper->success($brand);
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

        $brand = $this->dbHelper->getDocument($id);

        if (!$brand) {
            return $this->responseHelper->error('Brand ' . config('messages.not_found'), 404);
        }

        return $this->responseHelper->success($brand);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Brand $brand
     * @return \Illuminate\Http\Response
     */
    public function edit(Brand $brand)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Brand $brand
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        $id = $request->query('id');
        if (!$id) {
            return $this->responseHelper->error(config('messages.id_required'), 400);
        }

        $brand = $this->dbHelper->getDocument($id);
        if (!$brand) {
            return $this->responseHelper->error('Brand ' . config('messages.not_found'), 404);
        }

        // Image Upload
        if ($request->has('image')) {
            $file = $request->file('image');
            $filename = rand() . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images/brands', $file, $filename);
        }

        $filename = $filename ?? $brand->image;
        $url = Storage::url('images/brands/' . $filename);

        $doc = $this->dbHelper->updateDocument($id, [
            'name' => $request->input('name') ?? $brand->name,
            'description' => $request->input('description') ?? $brand->description,
            'image' => $url,
        ]);

        return $this->responseHelper->success($doc);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Brand $brand
     * @return JsonResponse
     */
    public function destroy(Request $request)
    {
        $id = $request->query('id');
        if (!$id) {
            return $this->responseHelper->error(config('messages.id_required'), 400);
        }

        $brand = $this->dbHelper->getDocument($id);
        if (!$brand) {
            return $this->responseHelper->error('Brand ' . config('messages.not_found'), 404);
        }

        $this->dbHelper->deleteDocument($id);

        return $this->responseHelper->success('Brand ' . config('messages.deleted'));
    }

    /**
     * Limit in response represents the max page number
     **/
    // Get all brands
    public function all(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('name')) {
                $filters['name'] = $request->input('name');
            }
            $page = $request->query('page') ?? 1;
            $sort = $request->query('sort') ?? 'desc';
            $order = $request->query('orderBy') ?? 'created_at';
            $brands = $this->dbHelper->getDocuments(count($filters) == 0 ? null : $filters, $sort, $order, $page);
            if ($brands['total'] == 0) {
                return $this->responseHelper->error('Brands ' . config('messages.not_found'), 404);
            }
            return response()->json($brands);

        } catch (\Exception $e) {
            return $this->responseHelper->error($e->getMessage(), 400);
        }
    }

}
