<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request){
        try {
            $data = $request->validate([
                'name'=>['required','string','unique:users'],
                'email'=>['required','email','unique:users'],
                'password'=>['required','min:6'],
            ]);
            $user = User::create($data);
            $token = $user->createToken('auth_token')->plainTextToken;
          
            return [
                "ok"=>true,
                'user'=> $user,
                'token'=> $token,
            ];
        }catch (Exception $e) {
            Log::error($e);
            return ["ok"=>false,"message"=>"Error:".$e->getMessage()];                 
        }
    }

    public function login(Request $request){
        try {
            $data = $request->validate([
                'name'=>['required','string'],
                'password'=>['required','min:6'],
            ]);
            $user = User::where('name',$data['name'])->first();

            if(!$user){
                return response([
                    "ok"=>false,
                    'message'=>'El nombre de usuario no existe'
                ],401);
            }

            
            if(!Hash::check($data['password'],$user->password)){
                return response([
                    "ok"=>false,
                    'message'=>'Credenciales incorrectas'
                ],401);
            }
            $token = $user->createToken("auth_token")->plainTextToken;
            return response([
                "ok"=>true,
                'user'=> $user,
                'token'=> $token,
            ],200);
             
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al iniciar session',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }

    public function verificarToken(Request $request){
        try {
            if (Auth::check()) {
                $usuario = Auth::user();
                return response()->json([
                    'usuario' => $usuario,
                ]);
            } else {
                return response()->json(['error' => 'No autenticado'], 401);
            }
        }catch (Exception $e) {
            Log::error($e);
            return response([
                "ok"=>false,
                'message'=>'Error al verificar el token',
                "error"=>$e->getMessage()
            ],400);                 
        }
    }
}