<x-app-layout>
    <x-slot name="header">Profile</x-slot>
    <x-slot name="subtitle">Account details and password</x-slot>
    <x-slot name="title">Profile</x-slot>

    <div class="card card-pad" style="margin-bottom:18px;">
        @include('profile.partials.update-profile-information-form')
    </div>
    <div class="card card-pad">
        @include('profile.partials.update-password-form')
    </div>
</x-app-layout>
