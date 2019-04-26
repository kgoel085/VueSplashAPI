<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class CollectionController extends Controller
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
        $this->currentEndpoint = '/collections';
        $this->validParams = array('page', 'per_page', 'order_by', 'featured');
    }

    //Returns the data for index page
    public function index(Request $request){
        $queryArr = array();

        foreach($request->all() as $reqKeys => $reqVars){
            if(in_array($reqKeys, $this->validParams) && $reqVars){
                $queryArr[$reqKeys] = $reqVars;
            }
        }

        //Checks whether featured collections are requested or not 
        $currentEndpoint = ($request->featured) ? $this->currentEndpoint.'/featured' : '';

        return $this->performRequest($currentEndpoint, $queryArr);
    }
    
    //Returns details for the required collection id
    public function getCollection(Request $request, $collectionId = 0, $action = null){
        $endPoint = (empty($collectionId) == false) ? $this->currentEndpoint.'/'.$collectionId : $this->currentEndpoint;
        if(empty($endPoint) == false && empty($action == false)) $endPoint  = $endPoint."/".$action;
        return $this->performRequest($endPoint);
    }

    //Extract HTTP headers and return the required values
    private function getHeaderResponse($httpResponse = array()){
        $returnArr = array();
        if(count($httpResponse) == 0) return $returnArr;

        //Total number of pages
        if($httpResponse->getHeader('X-Total')){
            $totalPages = $httpResponse->getHeader('X-Total');
            $returnArr['pagination']['total_pages'] = $totalPages[0];
        }

        //Paginations
        if($httpResponse->getHeader('Link')){
            $linkString = $httpResponse->getHeader('Link');
            $tmpArr = explode(',', $linkString[0]);

            if($tmpArr && count($tmpArr) > 0){
                foreach($tmpArr as $tmpObj){
                    list($link, $linkType) = explode(';', str_ireplace(array('rel=', '"', '<', '>'), '', $tmpObj));
                    if($link && $linkType){
                        $link = parse_url($link);
                        if($link['query']){
                            $link = $link['query'];
                            if(stripos($link, '&') === false){
                                $link = str_ireplace('page=', '', trim($link));
                                $linkType = str_ireplace('page=', '', trim($linkType));

                                if($linkType == 'next') $linkType = 'page';
                                $returnArr['pagination'][$linkType] = $link;
                            }else{
                                $paramTmp = explode('&', $link);
                                if(is_array($paramTmp) && count($paramTmp) > 0 ){
                                    foreach($paramTmp as $tmpPrm){
                                        if(stripos($tmpPrm, '=') === false){

                                        }else{
                                            list($prmName, $prmVal) = explode('=', $tmpPrm);
                                            if($prmName && $prmVal) $returnArr['pagination'][trim($prmName)] = str_ireplace('page=', '', trim($prmVal));
                                        }
                                    }
                                }
                            }
                            
                        } 
                    }
                }
            }
        }

        return $returnArr;
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
                $responseArr = array(
                    'data' => $responseBody
                );

                //Fetch required info from response headers
                $headersArr = $this->getHeaderResponse($response);
                if(count($headersArr) > 0) $responseArr['extra_info'] = $headersArr;
                
                //Return response
                return response()->json([
                    'success' => $responseArr
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
