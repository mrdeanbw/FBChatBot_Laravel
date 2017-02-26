<?php

$app->group(['prefix'=>'monitor'], function () use ($app) {

	
	
	$app->group(['namespace' => '\Modules\Monitor\Controllers'], function () use ($app){
		$app->get('/', 'MonitorController@index');
		$app->get('/db','DatabaseMonitorController@index');
	});    

		
	$app->get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');

});
