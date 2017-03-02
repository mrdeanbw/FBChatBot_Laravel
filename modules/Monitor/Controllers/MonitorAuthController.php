<?php namespace Modules\Monitor\Controllers;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class MonitorAuthController extends Controller
{
	public function login()
	{
		return view('modules-monitor::login');
	}

	public function do_login(Request $request)
	{
		$token =  $request->input('token');
		if($token != config('monitor.auth_token')){
    		return redirect('/monitor/login');
    	}
    	$_SESSION['MonitorLogged'] = $token;
    	return redirect('/monitor');
	}
}