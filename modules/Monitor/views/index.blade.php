@extends('modules-monitor::layout')
@section('title','Servers')
@section('content')
      

      <div class="well">
        <h2>Servers:</h2>
        @foreach($servers as $server)
          <h3>{{ $server['host'] }}</h3>
          <p>
            CPU Load : <b>{{ implode(',',$server['load']) }}</b>
          </p>
          <p>Used Disk Space: <b>{{ $server['diskspace'] }}</b></p>
          <p>
            Memory : 
              <b>{{ $server['memory']['taken'] }}</b> MB / 
              <b>{{ $server['memory']['total'] }}</b> MB ,
              ( <b>{{ $server['memory']['percent'] }}</b> %)

          </p>
          <hr />
        @endforeach
      </div>
      <div class="well">
        <h2>Logs: <a href="{{ url('/monitor/logs') }}">Show logs contents</a></h2>
        <table class="table">
        @foreach($logFiles as $log)
          <tr> 
            <td>{{ $log['name'] }}</td>
            <td><b>{{ $log['size']}}</b></td>
          </tr>
        @endforeach
        </table>
      </div>
@endsection