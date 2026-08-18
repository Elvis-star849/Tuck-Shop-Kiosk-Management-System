<x-app-layout>
    <x-slot name="header">Create invoice</x-slot>
    <x-slot name="subtitle">Select saved products, then save a draft or generate the invoice</x-slot>
    <x-slot name="title">Create invoice</x-slot>

    @include('invoices._form')
</x-app-layout>
