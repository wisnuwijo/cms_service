<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Kesalahan</title>
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon">
    <link rel="icon" href="./favicon.ico" type="image/x-icon">
</head>
<body>
    Terjadi kesalahan <br/>
    {{ $error_type }}
    @if (isset($error_data))
        {{ $error_data }}
    @endif
</body>
</html>
