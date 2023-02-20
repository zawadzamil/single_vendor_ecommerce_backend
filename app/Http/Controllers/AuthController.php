<?php

namespace App\Http\Controllers;

use App\Helpers\dbHelper;
use App\Helpers\FillableChecker;
use App\Helpers\ResponseHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    //
    protected dbHelper $dbHelper;
    protected FillableChecker $fillableChecker;
    protected ResponseHelper $responseHelper;
    public function __construct()
    {

        $this->dbHelper = new dbHelper(new User());
        $this->fillableChecker = new FillableChecker(new User());
        $this->responseHelper = new ResponseHelper();
    }


    public function login(Request $request)
    {
        $loginField = filter_var($request->input('email_or_username'), FILTER_VALIDATE_EMAIL) ? 'email' : 'username';


        $credentials = [
            $loginField => $request->input('email_or_username'),
            'password' => $request->input('password')
        ];

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['message' => 'Wrong Credentials'], 401);
        }

        $user =auth()->user();
        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate Required Fields
       $filled = $this->fillableChecker->check(['name', 'email', 'password','username'],$request);
       if(!$filled['success']){
           return response()->json(['message' => $filled['message']],422);
       }

        $username = str_replace(' ', '', $request->input('username'));
        $existingUser = $this->dbHelper->getDocuments(['username' =>$username]);

        if (count($existingUser) > 0) {
            return response()->json(['message' => 'Username already exists'], 409);
        }

       try {
           $user = $this->dbHelper->createDocument([
               'name' => $request->name,
               'email' => $request->email,
               'username' => $username,
               'password' => Hash::make($request->password),
           ]);
       }
       catch (\Exception $e) {
           return response()->json(['message' => $e->errorInfo[2]??'Something went wrong!'],400);
    }

        return $this->responseHelper->created($user,'User');
    }

    public function logout()
    {
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }
    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::user(),
            'authorisation' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ]);
    }

    // Get Current User
    public function me()
    {
        $user = Auth::user();
        $role = $user->getRoleNames();
        $data = collect($user->toArray())->forget(['created_at', 'updated_at', 'deleted_at', 'roles']);
        return response()->json([
            'user' => $data,
            'roles' => $role,


        ]);
    }


}
