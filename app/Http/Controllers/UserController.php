<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function validator(Request $arrData){
        $this->validate($arrData, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);
    }

    protected function create(array $data)
    {
        $NewUser = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'account' => sha1(time()),
            'password' => Hash::make($data['password']),
            
        ]);
        return $NewUser;
    }

    public function register(Request $request){
        $this->validator($request);

        $user = $this->create($request->all());

        if(!$user){
            return response()->json([
                'error' => 'Unable to process the request'
            ], 400);
        }

        // Add associated user id to the request object
        if($user) $request->auth = $user;

        return response()->json([
            'success' => 'User registered successfully'
        ], 200);
    }

    //
}
