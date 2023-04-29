@extends('errors::minimal')

@section('title', __($exception->getMessage() ?: 'Tidak ditemukan'))
@section('code', '404')
@section('message', __($exception->getMessage() ?: 'Tidak ditemukan'))
