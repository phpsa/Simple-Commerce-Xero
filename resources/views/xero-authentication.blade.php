@extends('statamic::layout')
@section('title', __('Xero Authentication'))

@section('content')
<div class="flex items-center justify-between">
    <h1>{{ __('Xero Authentication') }}</h1>
</div>

<div class="mt-3 card">
    @if($error)
    <h2>Your connection to Xero failed</h2>
    <p>{{ $error }}</p>
    <a href="{{ route('xero.auth.authorize') }}" class="btn btn-primary btn-large mt-4">
        Reconnect to Xero
    </a>
    @elseif($connected)
    <h2>You are connected to Xero</h2>
    <p>{{ $organisationName }} via {{ $username }}</p>
    <a href="{{ route('xero.auth.authorize') }}" class="btn btn-primary btn-large mt-4">
        Reconnect to Xero
    </a>
    @else
    <h2>You are not connected to Xero</h2>
    <a href="{{ route('xero.auth.authorize') }}" class="btn btn-primary btn-large mt-4">
        Connect to Xero
    </a>
    @endif
</div>
@stop
