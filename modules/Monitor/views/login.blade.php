@extends('modules-monitor::layout')
@section('title','Login')
@section('content')
	<div class="row">
		<div class="col-md-8 col-md-offset-2">
			<form method="post">
			  <div class="form-group">
			    <label for="exampleInputEmail1">Auth Token </label>
			    <input type="text" name="token" class="form-control" placeholder="Auth Token">
			  </div>
			  
			  <button type="submit" class="btn btn-default">Login</button>
			</form>
		</div>
	</div>
@endsection