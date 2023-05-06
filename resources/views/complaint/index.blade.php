@extends('layouts.app')

@section('title', 'Komplain')
@section('content')
<div class="col-md-12" style="margin-bottom:20px;">
    <div class="row" style="background: #eee;margin: 1px;padding: 10px;">
        <div class="col-md-4">
            Transaksi: Rp. {{ number_format($transaction->final_price,2,',','.') }}
        </div>
        <div class="col-md-8">
            <ul>
                @foreach($detail as $d)
                    <li>{{ $d->product_name }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

<form class="row g-3 needs-validation" method="post" action="{{ url('/save-complaint') }}">
    @csrf
    <div class="col-md-12">
        <label for="complaint-form" class="form-label">Deskripsi Keluhan</label>
        <input type="hidden" name="transaction_id" value="{{ $transaction->id }}">
        <textarea class="form-control" style="min-height: 150px;" id="complaint-form" name="text" required></textarea>
    </div>
    <input type="submit" class="btn-submit" value="Simpan">
</form>
@endsection

@section('javascript')
<script>
    var loadFile = function(event) {
        var output = document.getElementById('output');
        if (event.target.files.length > 0) {
            $('#preview').show();

            output.src = URL.createObjectURL(event.target.files[0]);
            output.onload = function() {
                URL.revokeObjectURL(output.src) // free memory
            }
        } else {
            $('#preview').hide();
            document.getElementById('output').removeAttribute("src");
        }
    };
</script>
@endsection
