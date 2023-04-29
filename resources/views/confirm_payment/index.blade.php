@extends('layouts.app')

@section('title', 'Pilih Transaksi')
@section('content')
@foreach($transactions as $trx)
<a href="{{ url()->current() }}/{{ $trx->id }}" style="text-decoration:none;">
    <div class="card" style="border: 1px solid orange;margin-bottom:10px;">
        <div class="card-body">
            <h5 class="card-title">
                Total: Rp. {{ number_format($trx->final_price,2,',','.') }}
            </h5>
            <h6 class="card-subtitle mb-2 text-muted">
                {{ date("d M Y h:i", strtotime($trx->created_at)) }}
            </h6>
            <p class="card-text">
                <?php
                    $ppn = ($trx->final_price - $trx->delivery_fee) / 111 * 11;
                ?>

                PPN (11%): Rp. {{ number_format(($ppn),2,',','.') }}</br>
                Ongkir: Rp. {{ number_format(($trx->delivery_fee),2,',','.') }}
            </p>
            <p class="card-text">
                <ul class="list-group list-group-flush">
                    @foreach($trx->details as $dtl)
                        <li class="list-group-item" style="border-color: orange;">
                            {{ $dtl->product_name }} -
                            {{ $dtl->qty }}pcs -
                            Rp. {{ number_format($dtl->total_price,2,',','.') }}
                        </li>
                    @endforeach
                </ul>
            </p>
        </div>
    </div>
</a>
@endforeach
@endsection
