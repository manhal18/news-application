<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email','password');

        $user = Auth::attempt($credentials);

        if($user)
        {

            $final_user = User::where('email',$request->email)->first();

            $final_user->tokens()->delete();

            $token = $final_user->createToken($request->device_name)->plainTextToken;

            return response()->json(['user'=>$final_user,'token'=>$token]);

        }else{

            return response()->json('Email or Password is Invalid.',401);

        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|unique:users,email|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {

            return response()->json($validator->errors(),400);

        }else{

            $new_user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $new_user->createToken($request->device_name)->plainTextToken;

            return response()->json(['user'=>$new_user,'token'=>$token]);

        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
        ]);

        if ($validator->fails()) {

            return response()->json($validator->errors(),400);

        }else{

            $response = User::find($id)->update([
                'password' => Hash::make($request->password),
            ]);

            User::find(Auth::id())->tokens()->delete();

            return response()->json([$response]);

        }
    }

    // public function sendResetLinkResponse(Request $request)
    // {
    //     $input = $request->only('email');
    //     $validator = Validator::make($input, [
    //     'email' => "required|email"
    //     ]);
    //     if ($validator->fails()) {
    //     return response(['errors'=>$validator->errors()->all()], 422);
    //     }
    //     $response =  Password::sendResetLink($input);
    //     if($response == Password::RESET_LINK_SENT){
    //     $message = "Mail send successfully";
    //     }else{
    //     $message = "Email could not be sent to this email address";
    //     }
    //     //$message = $response == Password::RESET_LINK_SENT ? 'Mail send successfully' : GLOBAL_SOMETHING_WANTS_TO_WRONG;
    //     $response = ['data'=>'','message' => $message];
    //     return response($response, 200);
    // }
}
