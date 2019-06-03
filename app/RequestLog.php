<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class RequestLog extends Model
{

    protected $table = "request_logs";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ip', 'server_url', 'user_id', 'route_path', 'request', 'request_method', 'response_status', 'response', 'server'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'user_id'
    ];

    public function permissions() {
        return $this->belongsToMany(Permission::class,'roles_permissions');
    }

    public function user(){
        return $this->belongsTo(User::class, 'users');
    }

    // Returns all the columns in the table
    public function getTableColumns(){
        return Schema::getColumnListing($this->getTable());
    }

    // Insert request logs
    public function insertLogs($request = false, $response = false){
        // Route Array
        //$routeObj = app()->router->getRoutes()[$request->method() . $request->getPathInfo()];
        $routeObj = (array_key_exists(1,$request->route())) ? $request->route()[1] : false;

        if($routeObj){
            // Add current route current path in object
            if($request->getPathInfo()) $routeObj['uri'] = $request->getPathInfo();

            // Format Called Server && API Endpoint
            if($routeObj['uri']){
                $tmpArr = array_values(array_filter(explode('/', $routeObj['uri'])));

                // Called API for
                $routeObj['server'] = array_shift($tmpArr);
                
                // Route path
                if(count($tmpArr) > 0) $routeObj['endpoint'] = '/'.implode('/', $tmpArr);
            }
        }

        // Manage Request data
        if($request){
            $reqCols = ($this->getTableColumns()) ? $this->getTableColumns() : [];

            $reqArr = [];
            if(count($reqCols) > 0){
                // User ID
                if($request->auth && $request->auth->id) $reqArr['user_id'] = $request->auth->id;

                // Check for Real IP
                if($this->checkIP($request)) $reqArr['ip'] = $this->checkIP($request);

                // API Auth Token
                if($request->bearerToken()) $reqArr['token'] = $request->bearerToken();

                // Server / Route paths
                if($routeObj['server']) $reqArr['server'] = $routeObj['server'];
                if($request->getPathInfo()) $reqArr['route_path'] = $routeObj['endpoint'];

                // Req method
                if($request->getMethod()) $reqArr['request_method'] = $request->method(); 

                // Request data
                $tmpReqArr = [];    
                // Request Parameters
                if($request->all()) $tmpReqArr['params'] = $request->all();

                // Request Headers
                if($request->headers->all()) $tmpReqArr['Headers'] = $request->headers->all();
                if(count($tmpReqArr) > 0) $reqArr['request'] = json_encode($tmpReqArr);

                // Response status
                if($response->status()) $reqArr['response_status'] = $response->status();

                // Response data
                $tmpResponseArr = [];
                if($response->headers->all()) $tmpResponseArr['headers'] = $response->headers->all();
                if($response->getContent()) $tmpResponseArr['response'] = $response->getContent();
                if(count($tmpResponseArr) > 0) $reqArr['response'] = json_encode($tmpResponseArr);

                if(count($reqArr) > 0){
                    $this->create($reqArr)->save();
                }
            }
        }
    }

    // Check for request / origin IP address
    protected function checkIP($req = false){
        $returnVal = false;
        if(!$req) return $returnVal;

        // Check for any forwarded IP's or Rela IP from balancer server's
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $ip){
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                        $returnVal = $ip;
                    }
                }
            }
        }

        // If still no ip found , use laravel IP function
        if(!$returnVal && $req->ip()) $returnVal = $req->ip();

        return $returnVal;
    }
}
