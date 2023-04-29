@extends('layouts.app')

@section('title', 'Konfirmasi Pembayaran')
@section('content')
<div class="col-md-12" style="margin-bottom:20px;">
    <div class="row" style="background: #eee;margin: 1px;padding: 10px;">
        <div class="col-md-4">
            Total: Rp. {{ number_format($transaction->final_price,2,',','.') }}
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

<form class="row g-3 needs-validation" method="post" action="{{ url('/confirm-payment') }}" enctype="multipart/form-data">
    @csrf
    <div class="col-md-12">
        <label for="bank-account-owner" class="form-label">Nama Pemilik Rekening</label>
        <input type="hidden" name="transaction_id" value="{{ $transaction->id }}">
        <input type="text" class="form-control" id="bank-account-owner" name="bank_account_owner" placeholder="" required>
    </div>
    <div class="col-md-12">
        <label for="bank-account" class="form-label">Nomor Rekening Asal</label>
        <input type="number" class="form-control" id="bank-account" name="bank_account" placeholder="" required>
    </div>
    <div class="col-md-12">
        <label for="amount-of-transfer" class="form-label">Jumlah Transfer</label>
        <input type="number" class="form-control" id="amount-of-transfer" value="{{ $transaction->final_price }}" placeholder="" readonly disabled>
        <input type="hidden" class="form-control" name="amount_of_transfer" value="{{ $transaction->final_price }}">
    </div>
    <div class="col-md-12">
        <label for="transfer-date" class="form-label">Tanggal Transfer</label>
        <input type="date" class="form-control" id="transfer-date" name="transfer_date" placeholder="" required>
    </div>
    <div class="col-md-12">
        <label for="transfer-time" class="form-label">Waktu Transfer (WIB)</label>
        <input type="time" class="form-control" id="transfer-time" name="transfer_time" placeholder="" required>
    </div>
    <div class="col-md-12">
        <label for="transfer-proof" class="form-label">Bukti Transfer</label>
        <input type="file" accept="image/*" class="form-control" onchange="loadFile(event)" id="transfer-proof" name="transfer_proof" placeholder="" required>
    </div>
    <div class="col-md-12" style="margin-bottom: 120px;">
        <div class="row" style="background: #eee;margin: 1px;padding: 10px;display:none;" id="preview">
            <div class="col-md-3">
                <img id="output" style="max-width:40px;" />
            </div>
            <div class="col-md-9">
                Preview
            </div>
        </div>
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
