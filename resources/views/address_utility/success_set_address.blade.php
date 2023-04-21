<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <title>Alamat berhasil disimpan</title>
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon">
    <link rel="icon" href="./favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="container belili-container"">
        <h6>Atur Alamat</h6>
        <hr style="background-color: #eca728;height: 2px;margin-top:10px;" />
        <div class="alert alert-success" role="alert">
            Alamat kamu berhasil disimpan, mengembalikan ke whatsapp dalam 5 detik ...
        </div>
    </div>
</body>
<style>
    .belili-container {
        max-width:412px;
        padding: 2%;
    }

    .btn-submit {
        background-color: #eca728;
        font-weight: bold;
        border: 0px;
        width: 100%;
        color: white;
        padding: 5px;
        border-radius: 10px;
    }

    /* On screens that are 600px or less, set the background color to olive */
    @media screen and (max-width: 412px) {
        .belili-container {
            padding: 5%;
        }

        .btn-submit {
            background-color: #eca728;
            font-weight: bold;
            border: 0px;
            width:95%;
            position: absolute;
            bottom:10px;
            color: white;
            padding: 5px;
            border-radius: 10px;
        }
    }
</style>
<script src="https://code.jquery.com/jquery-3.6.4.min.js" integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>
<script>
    $(function() {
        function task() {
            window.location.replace("https://wa.me/6281326887702");
        }

        setTimeout(task, 5000);
    })
</script>
</html>
