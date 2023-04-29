@extends('errors::minimal')

@section('title',__($exception->getMessage() ?: 'Page Expired'))
@section('code', '419')
@section('message',__($exception->getMessage() ?: 'Page Expired'))
