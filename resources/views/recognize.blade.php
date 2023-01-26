<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>

@if( $error != '')
    <h2 style="color:red">{{ $error }}</h2>
@else
    <h2>{{ $figure }}</h2>

    <p><a href="{{ route('form') }}">New upload</a></p>
    <img src="/uploads/{{ $filename }}" style="max-width: 500px" alt="{{ $figure }}" />
@endif

</body>
</html>
