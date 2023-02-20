<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;

class ResponseHelper
{

    public function created($response, $model){
        return response()->json([
           'message' =>$model .' created successfully.',
            'data' => $response
        ],201);
    }
    public function success($response): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'data' => $response
        ],200);
    }
    public function successWithMessage($response): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => $response,
        ],200);
    }
    // Success with message and data
    public function successWithMessageAndData($message,$data): \Illuminate\Http\JsonResponse
    {
        return response()->json([
           'message' => $message,
            'data' => $data
        ],200);
    }

    public function error($response,$status)
    {
        return response()->json([
            'message' => $response ?? 'Something went wrong'
        ], $status??500);
    }

}
