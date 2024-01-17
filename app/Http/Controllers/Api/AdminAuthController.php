<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{
    User
};
use Hash;
use Validator;
use Auth;
use DB;
use JWTAuth;
class AdminAuthController extends Controller
{
    public function register(Request $request)
    {
        try{
            $validator = Validator::make($request->all(),[
                'name'      => 'required|max:255',
                'role'   => 'required', 
                'email'     => 'required|email|unique:users,email',
                'password'  => 'required|string|min:6',
                
            ]);
            if ($validator->fails())
            {
                $errors = $validator->errors()->getMessages();
                $transformed = [];
                foreach ($errors as $field => $messages)
                {
                    $transformed[$field] = $messages[0];
                    $msg = $messages[0];
                    break;
                }
                return response()->json(['status' => 400,'message' => $msg],200);
            }

            DB::beginTransaction();

            $admin_detail = new User;
            $admin_detail->name          = $request->name;
            $admin_detail->role          = 'admin';
            $admin_detail->is_admin      = 'Y';
            $admin_detail->email         = $request->email;
            $admin_detail->password      = Hash::make('12345678');
            if ($admin_detail->save()) {
                User::where('id',$admin_detail->id)->update(['created_by'=>$admin_detail->id]);
            }
            
            DB::commit();
            $data = [
                'admin_detail' => $admin_detail,
            ];

            return response()->json(['data' => $admin_detail, 'status' => 200, 'message' => __('messages.Admin created successfully')], 200);
        }catch (Exception $e) {
            
            return \Response::json(['error'=> ['message'=>$e->getMessdob()]], HttpResponse::HTTP_CONFLICT)->setCallback(Input::get('callback'));
        }
    }


    public function login(Request $request)
    {
        if($request->isMethod('post'))
        {
            $validator = Validator::make($request->all(),[
                'email'      => 'required',
                'password'   =>'required',
            ],
            [
                'email.required'    => 'Please enter email.',
                'password.required'    => 'Please enter password.',
            ]);
            if ($validator->fails())
            {
                $messages = $validator->messages();
                foreach ($messages->all() as $message)
                {
                    Toastr::error($message, 'Failed', ['timeOut' => 5000]);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }else{
                $email = $request->email;
                $password = $request->password;
               
                if(Auth::attempt(['email' => $email, 'password' => $password]))
                {  
                    if((Auth::user()->role == 'admin'))
                    {  
                        $credentials = $request->only('email', 'password');

                        try {
                            if (! $token = JWTAuth::attempt($credentials)) {
                                return response()->json(['response_code' => 401, 'status' => false, 'message' => 'Invalid credentials'], 401);
                            }
                        } catch (JWTException $e) {
                            return response()->json(['response_code' => 500, 'status' => false, 'message' => 'Could not create token'], 500);
                        }

                        $user = Auth::user();
                        $result['id'] = $user->id;
                        $result['email'] = $user->email;
                        $result['name'] = $user->name;
                        $result['is_verified'] = $user->is_verified;
                        $result['role'] = $user->role;
                        return response()->json(['data' => $result, 'token' => $token, 'role' => 'admin','message'=>'login successfully','response_code'=>200],200);
                    }else{

                    }
                }else{   
                    return response()->json(['response_code' => 422,'status' => false,'message' => 'Password mismatch'],200);
                }
            }
        }else{
           return response()->json(['message'=>'invalid','response_code'=>5000],5000);
        }
    }
}
