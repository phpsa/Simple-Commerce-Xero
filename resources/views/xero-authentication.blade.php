@extends('statamic::layout')
@section('title', __('Xero Authentication'))

@section('content')
<div class="flex items-center justify-between">
    <h1>{{ __('Xero Authentication') }}</h1>
</div>

<div class="mt-3 card bg-grey-20 border-b mb-6">
    <div class="flex justify-between items-center ">
        <div class="pr-4">
            @if($error)
            <h2 class="font-bold">Your connection to Xero failed</h2>
            <p class="text-grey text-sm my-1">{{ $error }}</p>

            @elseif($connected)
            <h2 class="font-bold">You are connected to Xero</h2>
            <p class="text-grey text-sm my-1">{{ $organisationName }} via {{ $username }}</p>

            @else
            <h2 class="font-bold">You are not connected to Xero</h2>
            <p class="text-grey text-sm my-1">To get started you will need to connect yoru Xero organisation to
                Statamic.
                Use
                the button to begin the
                authentication process.</p>

            @endif
        </div>
        <a href="{{ route('xero.auth.setup') }}" class="btn btn-primary btn-large mt-4">
            @if ($error || $connected )
            Reconnect
            @else
            Connect
            @endif to Xero
        </a>
    </div>

</div>
@if ($connected && !$error)
<publish-form title="Xero Cost Code Mapping" action="{{ cp_route('utilities.xero-authentication.update') }}"
    :blueprint='@json($blueprint)' :meta='@json($meta)' :values='@json($values)'></publish-form>
@endif
@stop
