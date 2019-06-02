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
        'ip', 'server_url', 'user_id', 'route_path', 'request', 'request_method', 'response_status', 'response'
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
        // Manage Request data
        if($request && $request->auth){
            $reqCols = ($this->getTableColumns()) ? $this->getTableColumns() : [];

            $reqArr = [];
            if(count($reqCols) > 0){
                // User ID
                if($request->auth->id) $reqArr['user_id'] = $request->auth->id;

                // Check for Real IP
                if($this->checkIP($request)) $reqArr['ip'] = $this->checkIP($request);

                // Server / Route paths
                if(url()) $reqArr['server_url'] = url();
                if($request->getPathInfo()) $reqArr['route_path'] = $request->getPathInfo();

                // Req method
                if($request->getMethod()) $reqArr['request_method'] = $request->getMethod(); 

                // Req status
                if($response->status()) $reqArr['response_status'] = $response->status();

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
