@extends('modules-monitor::layout')
@section('title','Database')
@section('content')
	<div class="well">
		<h2>General Info</h2>
		<p> Database Name: <b>{{ $info['db'] }}</b></p>
		<p>Total DB Size: <b>{{ $converter($info['dataSize'])  }}</b> , Allocated <b>{{  $converter($info['storageSize']) }} </b></p>
		<p>Number of collections: <b>{{ $info['collections'] }}</b></p>
		<p>Number of documents: <b>{{ $info['objects'] }}</b> , Average size of document: <b>{{ $converter($info['avgObjSize']) }}</b></p>
		
		<p>Number of indexes : <b>{{ $info['indexes'] }}</b> , Total indexes size : <b>{{ $converter($info['indexSize']) }}</b></p>
	</div>
	<div class="well">
		<h2>Indexes</h2>
		@foreach($indexes as $collectionName => $indexesDetails)
			<h3>{{ $collectionName }}</h3>
			<p>Total data Size: <b>{{ $converter($indexesDetails['size']) }}</b></p>
			<p>Total Indexes Size: <b>{{ $converter($indexesDetails['totalIndexSize']) }}</b></p>
			<p>
			@foreach($indexesDetails['indexSizes'] as $key=>$size)
				<code><b>{{ $key }}</b> => <b>{{ $converter($size) }}</b></code> , 
			@endforeach
			</p>
		@endforeach
	</div>
	<div class="well">
		<h2>Top 10 slowest queries</h2>
		<ul class="list-group">
		@foreach($slow as $squery)
			<li class="list-group-item">
				<span class="badge">{{carbon_date($squery['ts'])->diffForHumans()}}</span>
				<span class="badge">{{ $squery['millis'] }} ms</span>
				<a href="javascript:;" data-toggle="modal" data-target="#q{{ $loop->index}}">					
					[{{ $squery['op'] }}][{{ $squery['ns'] }}]
				</a>
			
				<!-- Modal -->
				<div class="modal fade" id="q{{ $loop->index}}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
				  <div class="modal-dialog modal-lg" role="document">
				    <div class="modal-content">
				      <div class="modal-header">
				        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				        <h4 class="modal-title" id="myModalLabel">Query Info</h4>
				      </div>
				      <div class="modal-body">
				        <pre>{{ print_r($squery,1) }}</pre>
				      </div>
				      <div class="modal-footer">
				        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				      </div>
				    </div>
				  </div>
				</div>
			</li>
		@endforeach
		</ul>
	</div>


	<div class="well">
		<h2>Lastest 15 queries made</h2>
		<ul class="list-group">
		@foreach($latest as $lquery)
			<li class="list-group-item">
				<span class="badge">{{carbon_date($lquery['ts'])->diffForHumans()}}</span>
				<span class="badge">{{ $lquery['millis'] }} ms</span>
				<a href="javascript:;" data-toggle="modal" data-target="#q{{ $loop->index}}">					
					[{{ $lquery['op'] }}][{{ $lquery['ns'] }}]
				</a>
				<!-- Modal -->
				<div class="modal fade" id="q{{ $loop->index}}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
				  <div class="modal-dialog modal-lg" role="document">
				    <div class="modal-content">
				      <div class="modal-header">
				        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				        <h4 class="modal-title" id="myModalLabel">Query Info</h4>
				      </div>
				      <div class="modal-body">
				        <pre>{{ print_r($lquery,1) }}</pre>
				      </div>
				      <div class="modal-footer">
				        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				      </div>
				    </div>
				  </div>
				</div>
			</li>
		@endforeach
		</ul>
	</div>
@endsection