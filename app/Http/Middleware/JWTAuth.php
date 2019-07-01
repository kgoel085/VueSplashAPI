<?php

namespace App\Http\Middleware;

use Closure;

use Exception;
use App\User;
Use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class JWTAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = null;

        try {
            /**
            * Get token from cookies
            */
            $tokenArr = [];

            // Payload
                if($request->cookie(env('JWT_COOKIE_PAYLOAD'))) $tokenArr[] = Crypt::decrypt($request->cookie(env('JWT_COOKIE_PAYLOAD')));

            // Signature
                if($request->cookie(env('JWT_COOKIE_SIG'))) $tokenArr[] = Crypt::decrypt($request->cookie(env('JWT_COOKIE_SIG')));

            // Make JWT token
                if(count($tokenArr) > 0) $token = implode('.', $tokenArr);

            /**
             * Get the token from Bearer as it is the only valid way to send the token
             */
            // if($request->header('Authorization')){
            //     $tmpArr = explode(' ',$request->header('Authorization'));
            //     if(end($tmpArr)) $token = end($tmpArr);
            // }

            // Store the current authorized user token
            if($request->cookie(env('JWT_COOKIE_LOGIN')) && !is_array($request->cookie(env('JWT_COOKIE_LOGIN')))){
                $loginAuth = Crypt::decrypt($request->cookie(env('JWT_COOKIE_LOGIN')));
                if($loginAuth) $request->unsplashUser = $loginAuth;
            }

            if(!$token) {
                // Unauthorized response if token not there
                return response()->json([
                    'error' => 'Token not provided.'
                ], 401);
            }

            $credentials = JWT::decode($token, env('JWT_SECRET'), [env('JWT_ALGO', 'HS256')]);

            //Check the issuer is valid or not
            if(!Hash::check(env('APP_NAME'), $credentials->iss)){
                throw new Exception('Issuer is invalid');
            }

            $credentials->iss = env('APP_NAME');
            
        } catch(ExpiredException $e) {
            return response()->json([
                'error' => 'Provided token is expired.'
            ], 400);
        } catch(Exception $e) {
            return response()->json([
                'error' => 'An error while decoding token. '.$e->getMessage()
            ], 400);
        }

        $user = User::find($credentials->sub);

        //Check for user permission
        // if(!$user->can('read')){
        //     return response()->json([
        //         'error' => 'Unauthorized access'
        //     ], 400);
        // }
        
        // Now let's put the user in the request class so that you can grab it from there
        if($user){
            $request->auth = $user;

            //Add decoded token details in current request
            $request->jwt = $credentials;
        }

        return $next($request);
    }
}
