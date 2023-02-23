<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ValidationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $all_data = $request->all();
        $commonValidator = 'required|min:1';

        $keyMap = [
            'email' => 'required|string|max:100|email',
            'phone' => 'required|min:11|max:15',
            'customer_phone' => 'required|min:11|max:15',
            'password' => 'required|string|min:8|max:100',
            'common' => $commonValidator,
            'name'=>'required|string|max:200',
            'email_or_username'=>'required|string|max:200',
            'permission_name'=>'required|string|max:200|unique:permissions,name',
            'role_name'=>'required|max:200|min:2|string',
            'slug'=>'required|max:200|min:2|string',
            'permission'=>'max:5000',
            'image' => 'required|image|mimes:png,jpg,gif,svg|max:2048',
            'address' => 'required|string|max:500|min:2',
            'amount' => 'required|int|max:999999999|min:0',
            'price' => 'required|int|max:999999999|min:0',
            'minPrice' => 'required|int|max:999999999|min:0',
            'maxPrice' => 'required|int|max:999999999|min:0',
            'point' => 'required||numeric|gt:0|max:5',
            'quantity' => 'required|int|max:9999999|min:0',
            'duration' => 'required|string|max:100|min:2',
            'description' => 'required|string|min:2|max:10000',
            'details' => 'required|string|min:2|max:1000',
            'service_charge_type' => 'required|string|min:2|max:10',
            'note' => 'required|string|min:2|max:255',
            'title' => 'required|string|min:2|max:255',
            'customer'=>'max:3000',
            'fromDate' => 'required|date_format:d/m/Y',
            'toDate' => 'required|date_format:d/m/Y',
            'date' => 'required|date_format:d/m/Y',
            'start_time' => 'required|date_format:h:i A',
            'end_time' => 'required|date_format:h:i A|after:start_time',
            'category_id'=>'',
            'brand_id'=>'required|string|min:2|max:50',
            'offer_id'=>'required|string|min:2|max:50',
            'gender'=>'required|string|in:male,female',
            'size'=>'required|string|in:XS,S,M,L,XL,XXL,XXXL',



        ];
        foreach ($all_data as $key => $value) {
            $validator = "";
            if (array_key_exists($key,$keyMap)) {
                $validator = $this->validator($key, $value, $keyMap[$key]);
            }
            else {
                $validator = $this->validator($key, $value, $keyMap['common']);
            }
            if ($validator->fails()) {
                $errorMessage = implode(" ", $validator->messages()->all());
                return response()->json(["message"=>$errorMessage],400);
            }
        }
        return $next($request);
    }
    public function validator($key, $value, $validationString): \Illuminate\Validation\Validator
    {
        return Validator::make([$key => $value], [
            $key => $validationString
        ]);
    }
}
