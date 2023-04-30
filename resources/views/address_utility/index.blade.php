<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <title>Atur Alamat</title>
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon">
    <link rel="icon" href="./favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="container belili-container"">
        <h6>Atur Alamat</h6>
        <hr style="background-color: #eca728;height: 2px;margin-top:10px;" />
        @if($error != "")
            <div class="alert alert-warning" role="alert">
                Oops sepertinya sedang ada masalah <br/>
                {{ $error }}
            </div>
        @endif
        <form class="row g-3 needs-validation" method="post" action="{{ url('/save-address') }}">
            @csrf
            <div class="col-md-12">
                <label for="receiver-name" class="form-label">Nama Penerima</label>
                <input type="hidden" class="form-control" name="client_id" value="{{ $client->id }}">
                <input type="hidden" class="form-control" name="pending_transaction_id" value="{{ $set_address->pending_transaction_id }}">
                <input type="hidden" class="form-control" name="origin" value="{{ $_GET['origin'] ?? '' }}">
                <input type="text" class="form-control" id="receiver-name" name="receiver_name" value="{{ $client->name }}" required>
            </div>
            <div class="col-md-12">
                <label for="receiver-phone-number" class="form-label">Nomor HP</label>
                <input type="number" oninvalid="this.setCustomValidity('Nomor HP tidak valid')" oninput="setCustomValidity('')" class="form-control" id="receiver-phone-number" value="{{ $client->phone_number }}" disabled>
                <input type="hidden" name="receiver_phone_number" value="{{ $client->phone_number }}">
            </div>
            <div class="col-md-12">
                <label for="province-form" class="form-label">Provinsi</label>
                <select class="form-select" aria-label="Default select example" name="province" id="province-form" required>
                    <option value="" selected>Pilih provinsi</option>
                    @foreach ($province_list as $p)
                        <option value="{{ $p->province_id }}">{{ $p->province }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-12">
                <label for="city-form" class="form-label">Kota</label>
                <select class="form-select" aria-label="Default select example" id="city-form" name="city" required>
                    <option value="" selected>Pilih kota</option>
                </select>
            </div>
            <div class="col-md-12">
                <label for="full-address" class="form-label">Alamat Lengkap</label>
                <input type="text" class="form-control" id="full-address" name="full_address" placeholder="Nama jalan beserta nomor" required>
            </div>
            <input type="submit" class="btn-submit" value="Simpan">
        </form>
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
    $('#province-form').change(function(e) {
        $('#city-form').children().remove();
        $('#city-form').append('<option value="" selected>Pilih kota</option>')

        var getCityListUrl = '{{ url('/v1/region/city') }}';
        var provinceValue = $('#province-form').val();

        $.get(getCityListUrl + "/" + provinceValue, function(data, status){
            for (i=0;i<data.data.length;i++) {
                var element = '<option value="'+ data.data[i].city_id +'">'+ data.data[i].city_name +'</option>';
                $('#city-form').append(element);
            }
        });
    })
</script>
</html>
