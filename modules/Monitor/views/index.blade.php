<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>Mr Reply Monitor</title>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>

    <div class="container">
      <div class="jumbotron">
        <h1>Mr Reply Monitor Center</h1>
      </div>

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
    </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
  </body>
</html>