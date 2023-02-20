<?php

namespace App\Http\Controllers;

use App\Helpers\dbHelper;
use App\Helpers\FillableChecker;
use App\Helpers\ResponseHelper;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    protected dbHelper $dbHelper;
    protected FillableChecker $fillableChecker;
    protected ResponseHelper $responseHelper;

    public function __construct()
    {

        $this->dbHelper = new dbHelper(new Role());
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
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $fillable = $this->fillableChecker->check(['name', 'permissions'], $request);
        if (!$fillable['success']) {
            return $this->responseHelper->error($fillable['message'], 400);
        }
        // Validate Permissions
        $permissions = $request->input('permissions');
        foreach ($permissions as $permission) {
            try {
                $permissionExists = Permission::findByName($permission);
            }
            catch (\Exception $e) {
                return $this->responseHelper->error(config('messages.permission_not_found').$permission,400);

            }
        }

        $toStore = [
            "name" => $request->input('name'),
        ];
        $role = $this->dbHelper->createDocument($toStore);
        $role->syncPermissions($permissions);
        return $this->responseHelper->success($role);


    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Role $role
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Role $role
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $id = $request->query('id');
        if(!$id){
            return $this->responseHelper->error(config('messages.id_required'), 400);
        }
        $role = $this->dbHelper->getDocument($id);
        if(!$role){
            return $this->responseHelper->error('Role '.config('messages.not_found'),404);
        }

        if($request->has('permissions')){
            $permissions = $request->input('permissions');
            foreach ($permissions as $permission) {
                try {
                    $permissionExists = Permission::findByName($permission);
                }
                catch (\Exception $e) {
                    return $this->responseHelper->error(config('messages.permission_not_found').$permission,400);

                }
            }
            $role->syncPermissions([]);
            $role->syncPermissions($permissions);
        }

        $role->name = $request->input('name') ?? $role->name;
        $role->save();
        return $this->responseHelper->success($role);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Role $role
     * @return JsonResponse
     */
    public function destroy(Request $request)
    {
        // Delete a role by id
        $id = $request->query('id');
        if(!$id){
            return $this->responseHelper->error(config('messages.id_required'),400);
        }
        $role = $this->dbHelper->getDocument($id);
        if(!$role){
            return $this->responseHelper->error('Role '.config('messages.not_found'),404);
        }
        $role->delete();

        return $this->responseHelper->successWithMessage('Role '.config('messages.deleted'));
    }
    // Assign role to user
    public function assign(Request $request){
        $user = User::find(1);

//        $role = Role::where('name', 'Super Admin')->first();
//
//        $user->assignRole($role);
    }
}
