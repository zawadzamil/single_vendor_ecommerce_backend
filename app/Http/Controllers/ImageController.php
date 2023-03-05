<?php

namespace App\Http\Controllers;

use App\Helpers\dbHelper;
use App\Helpers\FillableChecker;
use App\Helpers\ResponseHelper;
use App\Models\Image;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class ImageController extends Controller
{

    protected dbHelper $dbHelper;
    protected FillableChecker $fillableChecker;
    protected ResponseHelper $responseHelper;

    public function __construct()
    {

        $this->dbHelper = new dbHelper(new Image());
        $this->fillableChecker = new FillableChecker(new Product());
        $this->responseHelper = new ResponseHelper();
    }
    //Delete Product Image
    public function deleteProductImage(Request $request){
        $idValidate = $this->dbHelper->findByIdValidate($request);

        if (!$idValidate['success']) {
            return $this->responseHelper->error($idValidate['message'], $idValidate['status']);
        }

        $image = $idValidate['data'];
        $image->delete();

        return $this->responseHelper->successWithMessage('Product Image deleted');
    }

    // Make Primary Image
    public function makePrimaryImage(Request $request){
        $idValidate = $this->dbHelper->findByIdValidate($request);

        if (!$idValidate['success']) {
            return $this->responseHelper->error($idValidate['message'], $idValidate['status']);
        }

        $image = $idValidate['data'];
        $productId = $image->product_id;
        $productImages = Image::where('product_id',$productId)->update(['isPrimary'=>false]);
        $image->update(['isPrimary'=>true]);

        return $this->responseHelper->successWithMessage('Primary image changed.');

    }

}
