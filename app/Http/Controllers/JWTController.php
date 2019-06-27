<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Support\Facades\Crypt;

class JWTController extends Controller
{

    private $reqVars;
    private $authUser;
    private $expiryTime;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        if($request) $this->reqVars = $request;
        
        //Validate current user
        $this->validateUser();

        // Expiry time for token
        $this->expiryTime = time() + (env('JWT_EXPIRY') * 10);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator(Request $data)
    {
        $this->validate($data, [
            'email' => ['required', 'string', 'email', 'exists:users'],
            'password' => ['required', 'string'],
            'account' => ['required', 'string', 'exists:users']
        ]);
    }

    public function validateUser(){
       $authUSer = $this->reqVars->auth;
       if($authUSer) $this->authUser = $authUSer;

        if(!$this->authUser){

            //Validate the params
            $this->validator($this->reqVars);

            //Validate the user
            $user = User::where([['email', '=', $this->reqVars->input('email')], ['account', '=', $this->reqVars->input('account')]])->first();
            if(!$user){
                return response()->json(['error' => 'Invalid login provided.'], 401);
            }

            //validate the password
            if (!Hash::check($this->reqVars->input('password'), $user->password)) {
                return response()->json(['error' => 'Invalid password provided'], 401);
            }

            $this->authUser = $user;
        }
    }

    /**
     * Creates the JWT token based on received user
     * 
     * @param App\User
     * @return string \Firebase\JWT\JWT
     */
    public function jwtToken($arr = array()){
        $payload = [
            'iss' => Hash::make(env('APP_NAME')),
            'sub' => $this->authUser->id,
            'iat' => time(),
            'exp' => $this->expiryTime
        ];

        if(count($arr) > 0) $payload = array_merge($arr, $payload);

        return JWT::encode($payload, env('JWT_SECRET'));
    }

    /**
     * Validate received credentials and return JWT token
     */
    public function generateToken($tokenExtras = array()){
        $newToken = $this->jwtToken($tokenExtras);
        if($newToken) $newToken = explode('.', $newToken);

        $response = response()->json(['success' => true], 200);
        $cookieTime = $this->expiryTime;

        // Token Signature Cookie
        $tokenSig = array_pop($newToken);
        if($tokenSig){
            $encryptSignature = Crypt::encrypt($tokenSig);
            $response = $response->withCookie(new Cookie(env('JWT_COOKIE_SIG'), $encryptSignature, $cookieTime));
        }

        // Token Payload Cookie
        $tokenPayload = implode('.', $newToken);
        if($tokenPayload) $response = $response->withCookie(new Cookie(env('JWT_COOKIE_PAYLOAD'), Crypt::encrypt($tokenPayload), $cookieTime, null, null, false, false));

        // Add associated user id to the request object
        if($this->authUser) $this->reqVars->auth = $this->authUser;
        return $response;
    }
}
