@extends('layout.main')

@php
use Carbon\Carbon;
$leadTop = "Dubbel L, Dubbel N,";
$leadBig = "Dubbel genieten";

// Set the metadata
SEOMeta::setTitle('Welkom');
SEOMeta::setCanonical(url('/'));
@endphp

@push('header.navbar-class', ' navbar--no-shadow ')

@section('content')
{{-- Covid --}}
@include('content.home.corona')

{{-- Header --}}
@include('content.home.header')

{{-- Sponsors --}}
@include('content.home.sponsors')


{{-- Activities --}}
@include('content.home.activities')

{{-- News --}}
@include('content.home.news')

{{-- Links (SEO) --}}
@include('content.home.links')
@endsection
