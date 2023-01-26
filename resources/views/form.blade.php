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

<h2>Upload your image</h2>

<p style="font-weight:bold">Restriction of the current version: the shape must take up most of the image.</p>

<p>The restriction will be lifted in the next version</p>

<form method="post" enctype="multipart/form-data" action="{{ route('upload') }}">
    {!! csrf_field() !!}

    Image:  <input type="file" name="picture" />

    <p><input type="submit" value="Upload" /></p>

</form>

</body>
</html>
