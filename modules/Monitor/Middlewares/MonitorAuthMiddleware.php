<?php
namespace Modules\Monitor\Middlewares;

use Closure;

class MonitorAuthMiddleware
{

    public function handle($request, Closure $next)
    {
    	session_name('MrReplySID');
		session_start();  		

    	
    	if($request->is('monitor/login')){
    		return $next($request);
    	}

    	$value = (isset($_SESSION['MonitorLogged']))?$_SESSION['MonitorLogged']:'Null';
    	if($value != getenv('MONITOR_AUTH_TOKEN')){
    		return redirect('/monitor/login');
    	}

        return $next($request);
    }
}