<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{
    User,Seller,Product,Brand
};
use Hash;
use Validator;
use Auth;
use DB;
use JWTAuth;

class ProductController extends Controller
{
    public function addProduct(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'description' => 'required|string',
                'brands' => 'required|array|min:1',
                'brands.*.name' => 'required|string',
                'brands.*.detail' => 'required|string',
                'brands.*.image' => 'required|string',
                'brands.*.price' => 'required|numeric',
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
            $seller = Auth::user();
            $seller_id = Seller::select('id')->where(['user_id' => $seller->id])->first();
            $product = Product::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'seller_id' => $seller_id->id,
                'brands' => $request->input('brands'),
            ]);
            $brands = $request->input('brands');
            foreach ($brands as $brandData) {
                $filename = null;
                // if ($brandData['image']) {
                //     $file = $brandData['image'];
                //     $extension = $file->getClientOriginalExtension();
                //     $filename = 'profile_'.time().rand(1,100).'.'.$extension;
                //     $file->move('brand/image',$filename);
                // }
                $brand = new Brand([
                    'name' => $brandData['name'],
                    'detail' => $brandData['detail'],
                    'image' => $filename,
                    'price' => $brandData['price'],
                    'product_id' => $product->id,
                ]);
                $product->brands()->save($brand);
            }
            return response()->json(['message' => 'Product added successfully']);
        }catch (Exception $e) {   
            return \Response::json(['error'=> ['message'=>$e->getMessdob()]], HttpResponse::HTTP_CONFLICT)->setCallback(Input::get('callback'));
        }
    }

    public function productsList(Request $request)
    {
        try{
            $seller = auth()->user();
            $seller_id = Seller::select('id')->where(['user_id' => $seller->id])->first();
            $products = Product::where('seller_id', $seller_id->id)->with('brands')->paginate(10);
            return response()->json(['data'=>$products,'status' =>200,'message'=> __('messages.Products data get Successfully')], 200);

        }catch (Exception $e) {
            return \Response::json(['error'=> ['message'=>$e->getMessdob()]], HttpResponse::HTTP_CONFLICT)->setCallback(Input::get('callback'));
        }
        
    }

    public function deleteProduct(Request $request)
    {
        try{
            $seller = Auth::user();
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|integer',
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
            $seller_id = Seller::select('id')->where(['user_id' => $seller->id])->first();
            $product = Product::where('id', $request->product_id)
                ->where('seller_id', $seller_id->id)
                ->first();

            if (!$product) {
                return response()->json(['error' => 'Product not found or unauthorized.'], 404);
            }
            $product->delete();
            return response()->json(['data'=>$product,'status' =>200,'message'=> __('messages.Products deleted Successfully')], 200);

        }catch (Exception $e) {
            return \Response::json(['error'=> ['message'=>$e->getMessdob()]], HttpResponse::HTTP_CONFLICT)->setCallback(Input::get('callback'));
        }
    }
}
