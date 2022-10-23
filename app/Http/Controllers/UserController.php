<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Exceptions\JWTException;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = Auth::guard('user')->attempt($credentials)) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Invalid credentials'
                ], 400);
            }

            return response()->json([
                'status' => 200,
                'message' => 'User has been successfully logged in',
                'results' => Auth::guard('user')->user(),
                'token' => $token
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function me()
    {
        try {
            $user = Auth::guard('user')->user();
            
            return response()->json([
                'status' => 200,
                'message' => 'Users logged in',
                'results' => $user
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function logout()
    {    
        try {
            Auth::guard('user')->logout();
 
            return response()->json([
                'success' => 200,
                'message' => 'User has been successfully logged out'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'search' => ['string', 'nullable'],
                'sort_by' => ['required_with:sort_order', Rule::in('id', 'name')],
                'sort_order' => ['required_with:sort_by', Rule::in('asc', 'desc')],
                'per_page' => ['numeric']
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => $validator->errors()
                ], 422);
            }

            $search = $request->search;
            $sortBy = $request->sort_by;
            $sortOrder = $request->sort_order;
            $perPage = $request->per_page;

            $user = User::when($sortBy && $sortOrder, function ($query) use ($sortBy, $sortOrder) {
                    $query->orderBy($sortBy, $sortOrder);
                })
                ->when($search, function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                })
                ->paginate($perPage);
            
            return response()->json([
                'status' => 200,
                'message' => 'Users list',
                'results' => $user
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        try {
            $user = new User;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->save();
            
            return response()->json([
                'status' => 200,
                'message' => 'User has been successfully created',
                'results' => $user
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, $id)
    {
        try {
            $user = User::find($id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->save();
            
            return response()->json([
                'status' => 200,
                'message' => 'User has been successfully updated',
                'results' => $user
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $user = User::find($id);
            
            $user->delete();
            
            return response()->json([
                'status' => 200,
                'message' => 'User has been successfully deleted',
                'results' => $user
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function trash(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'search' => ['string', 'nullable'],
                'sort_by' => ['required_with:sort_order', Rule::in('id', 'name')],
                'sort_order' => ['required_with:sort_by', Rule::in('asc', 'desc')],
                'per_page' => ['numeric']
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => $validator->errors()
                ], 422);
            }

            $search = $request->search;
            $sortBy = $request->sort_by;
            $sortOrder = $request->sort_order;
            $perPage = $request->per_page;

            $user = User::onlyTrashed()
                ->when($sortBy && $sortOrder, function ($query) use ($sortBy, $sortOrder) {
                    $query->orderBy($sortBy, $sortOrder);
                })
                ->when($search, function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                })
                ->paginate($perPage);
            
            return response()->json([
                'status' => 200,
                'message' => 'Users trash list',
                'results' => $user
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function restore($id)
    {
        try {
            $user = User::onlyTrashed()->find($id);
            $user->restore();
            
            return response()->json([
                'status' => 200,
                'message' => 'User has been successfully restored',
                'results' => $user
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function restores()
    {
        try {
            $user = User::onlyTrashed();
            $user->restore();
            
            return response()->json([
                'status' => 200,
                'message' => 'User has been successfully restored',
                'results' => $user
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function forceDelete($id)
    {
        try {
            $user = User::onlyTrashed()->find($id);
            $user->forceDelete();
            
            return response()->json([
                'status' => 200,
                'message' => 'User has been successfully deleted',
                'results' => $user
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function forceDeletes()
    {
        try {
            $user = User::onlyTrashed();
            $user->forceDelete();
            
            return response()->json([
                'status' => 200,
                'message' => 'User has been successfully deleted',
                'results' => $user
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }
}
