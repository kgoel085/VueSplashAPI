<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class PhotosController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private $currentEndpoint;
     
    public function __construct()
    {
        $this->middleware('jwt.auth');
        $this->currentEndpoint = '/photos';
    }

    public function index(){
        return $this->performRequest();
    } 

    private function performRequest($endpoint = '', $sendParams = array()){
        try{
            if(empty($endpoint)) $endpoint = $this->currentEndpoint;

            //Prepare cURL client with configuration
            $client = new Client([
                'base_uri' => "https://api.unsplash.com"
            ]);

            // Send a request
            $headersArr = array(
                'Referer' => env('APP_URL'),
                'Accept-Version' => 'v1',
                'Authorization' => 'Client-ID '.env('CLIENT_ACCESS_KEY')
            );
            
            $response = $client->request('GET', $endpoint, [
                'query' => $sendParams,
                'headers' => $headersArr
            ]);

            //Get request response
            $responseBody = json_decode($response->getBody()->getContents(), true);

            if($responseBody){
                return response()->json([
                    'success' => $responseBody
                ], 200);
            }

        }catch(ClientException $e){
            $error = [
                'status' => 400,
                'message' => 'Error Ocurred !'
            ];
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();

            if(empty($responseBodyAsString) == false){
                $tmpStr = json_decode($responseBodyAsString, true);
                dd($tmpStr);
            }
            
            return response()->json([
                'error' => $error['message']
            ], $error['status']);
        }
    }

    //
}
