<?php

$app->group(['prefix'=>'monitor','middleware' =>'\Modules\Monitor\Middlewares\MonitorAuthMiddleware']	, function () use ($app) {

	
	
	$app->group(['namespace' => '\Modules\Monitor\Controllers'], function () use ($app){
		$app->get('/', 'MonitorController@index');
		$app->get('/db','DatabaseMonitorController@index');
		$app->get('/login','MonitorAuthController@login');
		$app->post('/login','MonitorAuthController@do_login');
	});    

		
	$app->get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');

});
