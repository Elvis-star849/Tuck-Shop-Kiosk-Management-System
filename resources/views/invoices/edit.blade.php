<x-app-layout>
    <x-slot name="header">Edit {{ $invoice->invoice_number }}</x-slot>
    <x-slot name="subtitle">Totals are recalculated on the server when you save</x-slot>
    <x-slot name="title">Edit invoice</x-slot>

    @include('invoices._form')
</x-app-layout>
