<x-guest-layout>
    <x-slot name="title">Page expired · {{ config('app.name') }}</x-slot>
    <h1>Page expired</h1>
    <p class="muted" style="margin-bottom:22px;">
        The login form was open too long, or the page was refreshed after the server restarted.
        Open a fresh login page and try again.
    </p>
    <a class="btn btn-primary" href="{{ route('login') }}" style="width:100%;justify-content:center;">Back to login</a>
</x-guest-layout>
