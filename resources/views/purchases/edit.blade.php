<x-app-layout>
    <x-slot name="header">Edit {{ $purchase->purchase_number }}</x-slot>
    <x-slot name="subtitle">Change supplier, items, or quantities. Stock is adjusted by the difference.</x-slot>
    <x-slot name="title">Edit purchase</x-slot>

    <form
        method="POST"
        action="{{ route('purchases.update', $purchase) }}"
        class="card card-pad"
        x-data="purchaseForm(
            @js($products),
            @js($suppliers),
            @js(old('items', $itemDefaults)),
            @js(old('supplier_id', $purchase->supplier_id)),
            @js(old('supplier_name', $purchase->supplier?->name)),
            @js($suggestedSku)
        )"
    >
        @csrf
        @method('PUT')
        @include('purchases._form')
        <div class="actions" style="margin-top:18px;">
            <button class="btn btn-primary" type="submit">Save purchase</button>
            <a class="btn btn-ghost" href="{{ route('purchases.show', $purchase) }}">Cancel</a>
        </div>
    </form>
</x-app-layout>
