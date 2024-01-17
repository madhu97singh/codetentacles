<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{
    User,Seller
};
use Hash;
use Validator;
use Auth;
use DB;
use JWTAuth;
class SellerAuthController extends Controller
{
    public function sellerRegister(Request $request)
    {
        $auth_user = Auth('api')->user();
        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:sellers',
                'mobile_no' => 'required|string|unique:sellers|max:15',
                'country' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'skills' => 'required|array',
                'password' => 'required|string|min:6',
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
            $skills = $request->input('skills');
            $skillsString = implode(",", $skills);

            $seller_detail = new Seller;
            $seller_detail->name          = $request->name;
            $seller_detail->mobile_no = $request->mobile_no;
            $seller_detail->country       = $request->country;
            $seller_detail->state         = $request->state;
            $seller_detail->skills        = $skillsString;
            $seller_detail->email         = $request->email;
            $seller_detail->password      = Hash::make('12345678');
            if ($seller_detail->save()) {
                $seller_user_detail = new User;
                $seller_user_detail->name       = $seller_detail->name;
                $seller_user_detail->role       = 'seller';
                $seller_user_detail->is_admin   = 'N';
                $seller_user_detail->email      = $seller_detail->email;
                $seller_user_detail->password   = $seller_detail->password;
                $seller_user_detail->created_by = $auth_user->id;
                if ($seller_user_detail->save()) {
                    Seller::where('email',$seller_user_detail->email)->update(['user_id' => $seller_user_detail->id]);
                }
            }
            
            DB::commit();
            $data = [
                'seller_detail' => $seller_detail,
            ];

            return response()->json(['data' => $seller_detail, 'status' => 200, 'message' => 'Seller created successfully'], 200);
        }catch (Exception $e) {
            
            return \Response::json(['error'=> ['message'=>$e->getMessdob()]], HttpResponse::HTTP_CONFLICT)->setCallback(Input::get('callback'));
        }
    }


    public function sellerLogin(Request $request)
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
                    if((Auth::user()->role == 'seller'))
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
                        return response()->json(['data' => $result, 'token' => $token, 'role' => 'seller','message'=>'login successfully','response_code'=>200],200);
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

    public function sellersList(Request $request)
    {
        try{
            $user = auth()->user();
            $sellers_list = Seller::paginate(10);
            return response()->json(['data'=>$sellers_list,'status' =>200,'message'=> 'Sellers data get Successfully'], 200);

        }catch (Exception $e) {
            return \Response::json(['error'=> ['message'=>$e->getMessdob()]], HttpResponse::HTTP_CONFLICT)->setCallback(Input::get('callback'));
        }
        
    }
}
