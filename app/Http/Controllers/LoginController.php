<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class LoginController extends BaseController
{
    public function initiate(){
        $returnArr = [
            'client_id' => env('CLIENT_ACCESS_KEY'),
            'response_type' => 'code'
        ];

        return response()->json(['success' => $returnArr], 200);
    }

    // Generate auth token to retrieve unsplash user details
    public function oauth(Request $request){
        $code = ($request->code) ? $request->code : false;

        if($code){
            try{
                $client = new Client();

                $response = $client->request('POST', 'https://unsplash.com/oauth/token', [
                    'form_params' => [
                        'client_id' => env('CLIENT_ACCESS_KEY'),
                        'client_secret' => env('CLIENT_SECRET_KEY'),
                        'code' => $code,
                        'grant_type' => 'authorization_code',
                        'redirect_uri' => 'http://localhost:8080/login'
                    ]
                ]);

                $responseBody = json_decode($response->getBody()->getContents(), true);
                
                if($responseBody){
                    $responseArr = array(
                        'data' => $responseBody
                    );
                        
                    //Return response
                    return response()->json([
                        'success' => $responseArr
                    ], 200);
                }
            }catch(ClientException $e){

                $response = $e->getResponse();
                $responseBodyAsString = $response->getBody()->getContents();

                if(empty($responseBodyAsString) == false){
                    $tmpStr = json_decode($responseBodyAsString, true);
                    if($tmpStr && is_array($tmpStr)){
                        if(array_key_exists('error', $tmpStr) && $tmpStr['error']){
                            $error['message'] = implode(','.PHP_EOL, array_unique($tmpStr));
                        }
                    }
                }
                
                return response()->json([
                    'error' => $error['message']
                ],400 );
            }
        }
    }
}
