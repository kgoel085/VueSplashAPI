<?php 

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;
use App\RequestLog;

class CORS
{
    protected $settings = array(
        'origin' => '*',    // Wide Open!
        'allowMethods' => 'GET,HEAD,PUT,POST,DELETE,PATCH,OPTIONS',
    );

    protected function setOrigin($req, $rsp) {
        $origin = $this->settings['origin'];
        if (is_callable($origin)) {
            // Call origin callback with request origin
            $origin = call_user_func($origin,
                        $req->header("Origin")
                    );
        }
        $rsp->header('Access-Control-Allow-Origin', $origin);
    }

    protected function setExposeHeaders($req, $rsp) {
        if (isset($this->settings['exposeHeaders'])) {
            $exposeHeaders = $this->settings['exposeHeaders'];
            if (is_array($exposeHeaders)) {
                $exposeHeaders = implode(", ", $exposeHeaders);
            }

            $rsp->header('Access-Control-Expose-Headers', $exposeHeaders);
        }
    }

    protected function setMaxAge($req, $rsp) {
        if (isset($this->settings['maxAge'])) {
            $rsp->header('Access-Control-Max-Age', $this->settings['maxAge']);
        }
    }

    protected function setAllowCredentials($req, $rsp) {
        if (isset($this->settings['allowCredentials']) && $this->settings['allowCredentials'] === True) {
            $rsp->header('Access-Control-Allow-Credentials', 'true');
        }
    }

    protected function setAllowMethods($req, $rsp) {
        if (isset($this->settings['allowMethods'])) {
            $allowMethods = $this->settings['allowMethods'];
            if (is_array($allowMethods)) {
                $allowMethods = implode(", ", $allowMethods);
            }

            $rsp->header('Access-Control-Allow-Methods', $allowMethods);
        }
    }

    protected function setAllowHeaders($req, $rsp) {
        if (isset($this->settings['allowHeaders'])) {
            $allowHeaders = $this->settings['allowHeaders'];
            if (is_array($allowHeaders)) {
                $allowHeaders = implode(", ", $allowHeaders);
            }
        }
        else {  // Otherwise, use request headers
            $allowHeaders = $req->header("Access-Control-Request-Headers");
        }
        if (isset($allowHeaders)) {
            $rsp->header('Access-Control-Allow-Headers', $allowHeaders);
        }
    }

    protected function setCorsHeaders($req, $rsp) {
        // Pre-flight
        if ($req->isMethod('OPTIONS')) {
            $this->setOrigin($req, $rsp);
            $this->setMaxAge($req, $rsp);
            $this->setAllowCredentials($req, $rsp);
            $this->setAllowMethods($req, $rsp);
            $this->setAllowHeaders($req, $rsp);
        }
        else {
            $this->setOrigin($req, $rsp);
            $this->setExposeHeaders($req, $rsp);
            $this->setAllowCredentials($req, $rsp);
        }
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        if ($request->isMethod('OPTIONS')) {
            $response = new Response("", 200);
        }
        else {
            $response = $next($request);
        }
        $this->setCorsHeaders($request, $response);
        return $response;
    }

    // Log every request in the DB after response is ready to be dispatched
    public function terminate($request, $response){
        if($request->auth){
            $reqLogs = new RequestLog;
            $reqCols = ($reqLogs->getTableColumns()) ? $reqLogs->getTableColumns() : [];

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
                
                $reqArr['request'] = json_encode($request);
                $reqArr['response'] = json_encode($response);
            }

            if(count($reqArr) > 0){
                $reqLogs->create($reqArr)->save();
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

?>