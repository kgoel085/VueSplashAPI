<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpFoundation\Cookie;
use Psr\Http\Message\ResponseInterface;

class EndpointController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private $currentEndpoint;
    private $authHeader;
     
    public function __construct(Request $request)
    {
        $this->middleware('jwt.auth');
        //$this->currentEndpoint = '/';
        $this->validParams = array('page', 'per_page', 'order_by', 'featured', 'query');
        
        if($request->unsplashUser) $this->authHeader = $request->unsplashUser; 
    }

    //Returns details for the required image id
    public function getPhoto(Request $request, $picId = 0, $action = null){
        $queryArr = array();
        $this->currentEndpoint = '/photos';

        foreach($request->all() as $reqKeys => $reqVars){
            if(in_array($reqKeys, $this->validParams) && $reqVars){
                $queryArr[$reqKeys] = $reqVars;
            }
        }

        $endPoint = (empty($picId) == false) ? $this->currentEndpoint.'/'.$picId : $this->currentEndpoint;
        if(empty($endPoint) == false && empty($action == false)) $endPoint  = $endPoint."/".$action;
        return $this->performRequest($endPoint, $queryArr);
    }

    //Returns details for the required image id
    public function getUser(Request $request, $username = 0, $action = null){
        $this->currentEndpoint = '/users';
        $queryArr = array();

        foreach($request->all() as $reqKeys => $reqVars){
            if(in_array($reqKeys, $this->validParams) && $reqVars){
                $queryArr[$reqKeys] = $reqVars;
            }
        }

        $endPoint = (empty($username) == false) ? $this->currentEndpoint.'/'.$username : $this->currentEndpoint;
        if(empty($endPoint) == false && empty($action == false)) $endPoint  = $endPoint."/".$action;

        // Change endpoint, if private date is requested
        if($username == 'init' && $action == 'me') $endPoint = '/me';

        return $this->performRequest($endPoint, $queryArr);
    }
    
    //Returns details for the required collection id
    public function getCollection(Request $request, $id = 0, $action = null){
        $queryArr = array();
        $this->currentEndpoint = '/collections';

        foreach($request->all() as $reqKeys => $reqVars){
            if(in_array($reqKeys, $this->validParams) && $reqVars){
                $queryArr[$reqKeys] = $reqVars;
            }
        }

        $endPoint = (empty($id) == false) ? $this->currentEndpoint.'/'.$id : $this->currentEndpoint;
        if(empty($endPoint) == false && empty($action == false)) $endPoint  = $endPoint."/".$action;

        return $this->performRequest($endPoint, $queryArr);
    }

    //Returns details for the required user/collection/photos
    public function getSearch(Request $request, $id = 0, $action = null){
        $queryArr = array();
        $this->currentEndpoint = '/search';

        foreach($request->all() as $reqKeys => $reqVars){
            if(in_array($reqKeys, $this->validParams) && $reqVars){
                $queryArr[$reqKeys] = $reqVars;
            }
        }

        $endPoint = (empty($id) == false) ? $this->currentEndpoint.'/'.$id : $this->currentEndpoint;
        if(empty($endPoint) == false && empty($action == false)) $queryArr['query'] = $action;

        return $this->performRequest($endPoint, $queryArr);
    }

    public function fetchSearch(Request $request){
        $queryArr = $endPointArr = array();
        $this->currentEndpoint = '/search';

        // Get all the request params
        foreach($request->all() as $reqKeys => $reqVars){
            if(in_array($reqKeys, $this->validParams) && $reqVars){
                $queryArr[$reqKeys] = $reqVars;
            }
        }

        // Endpoints to hit && also check if extra params are passed for endpoint
        $endPointArr['photos'] = "/search/photos";
        if($request->photos) $queryArr['photos'] = json_decode($request->photos, true);

        $endPointArr['collections'] = "/search/collections";
        if($request->collections) $queryArr['collections'] = json_decode($request->collections, true);

        $endPointArr['users'] = "/search/users";
        if($request->users) $queryArr['users'] = json_decode($request->users, true);

        return $this->performMultiRequest($endPointArr, $queryArr);
    }

    public function fetchUserDetails(Request $request, $username = false){
        $this->currentEndpoint = "/users/$username";

        $endpointArr = [
            'user' => $this->currentEndpoint.'/',
            //'portfolio' => $this->currentEndpoint.'/portfolio',
            'photos' => $this->currentEndpoint.'/photos',
            'collections' => $this->currentEndpoint.'/collections',
            'likes' => $this->currentEndpoint.'/likes',
            'statistics' => $this->currentEndpoint.'/statistics',
        ];

        return $this->performMultiRequest($endpointArr);
    }

    //Extract HTTP headers and return the required values
    private function getHeaderResponse($httpResponse = false){
        $returnArr = array();
        if(!$httpResponse) return $returnArr;

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

    private function performMultiRequest($endpoint = array(), $queryArr = array()){
        try{
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

            // Attach unsplash auth token, if present
            if($this->authHeader) $headersArr['Authorization'] = 'Bearer '.$this->authHeader;
            
            $promises = array();
            foreach($endpoint as $key =>$url){
                $tmpQuery = $queryArr;

                // Check if extra params for same endpoint was provided or not
                if(array_key_exists($key, $tmpQuery)){
                    $tmpQuery = $queryArr[$key];
                    if(array_key_exists('total_pages', $tmpQuery)) unset($tmpQuery['total_pages']);
                    if(array_key_exists('query', $queryArr)) $tmpQuery['query'] = $queryArr['query'];
                }
                $promises[$key] = $client->getAsync($url, ['query' => $tmpQuery, 'headers' => $headersArr]);
            }
            
            
            // Wait on all of the requests to complete. Throws a ConnectException
            // if any of the requests fail
            
            $results = Promise\unwrap($promises);

            // Wait for the requests to complete, even if some of them fail
            $results = Promise\settle($promises)->wait();

            $returnArr = array();

            foreach($results as $key => $result){
                $tmpArr = array();
                $responseBody = $headersArr = false;

                //Get request response
                $responseBody = json_decode($result['value']->getBody()->getContents(), true);
                if($responseBody) $tmpArr['data'] = $responseBody;

                if(count($tmpArr) > 0 && array_key_exists('data', $tmpArr)) $returnArr[$key] = $tmpArr;
            }

            if(count($returnArr) > 0){
                return response()->json([
                    'success' => $returnArr
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
                if($tmpStr && array_key_exists('errors', $tmpStr) && count($tmpStr['errors']) > 0){
                    $error['message'] = implode(','.PHP_EOL, array_unique($tmpStr['errors']));
                }
            }
            
            return response()->json([
                'error' => $error['message']
            ], $error['status']);
        }
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

            // Attach unsplash auth token, if present
            if($this->authHeader) $headersArr['Authorization'] = 'Bearer '.$this->authHeader;
            
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
                if($tmpStr && array_key_exists('errors', $tmpStr) && count($tmpStr['errors']) > 0){
                    $error['message'] = implode(','.PHP_EOL, array_unique($tmpStr['errors']));
                }
            }
            
            return response()->json([
                'error' => $error['message']
            ], $error['status']);
        }
    }

    // Log the user out
    public function logout(Request $request, $action){
        // Remove user related cookie
        if($action == 'user' && $request->cookie(env('JWT_COOKIE_LOGIN'))){
            return response()->json([
                'success' => true
            ], 200)->withCookie(new Cookie(env('JWT_COOKIE_LOGIN'), $request->cookie(env('JWT_COOKIE_LOGIN')), 0));
        }
    }
}
