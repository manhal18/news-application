<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Token;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function index()
    {
        $tokens = Token::all();
        return response()->json($tokens);
    }

    public function store(Request $request)
    {
        $tokens = Token::create(['token' => $request->token]);
        return response()->json($tokens);
    }

    public function checkTokenExist($token){
        $count = count(Token::where('token', $token)->get());
        return response()->json($count);
    }
}
