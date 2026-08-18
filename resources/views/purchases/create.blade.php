<x-app-layout>
    <x-slot name="header">New purchase</x-slot>
    <x-slot name="subtitle">Type a name to pick an existing record, or keep typing to create a new one</x-slot>
    <x-slot name="title">New purchase</x-slot>

    <form
        method="POST"
        action="{{ route('purchases.store') }}"
        class="card card-pad"
        x-data="purchaseForm(
            @js($products),
            @js($suppliers),
            @js(old('items', [])),
            @js(old('supplier_id')),
            @js(old('supplier_name')),
            @js($suggestedSku)
        )"
    >
        @csrf
        @include('purchases._form')
        <div class="actions" style="margin-top:18px;">
            <button class="btn btn-primary" type="submit">Record purchase</button>
            <a class="btn btn-ghost" href="{{ route('purchases.index') }}">Cancel</a>
        </div>
    </form>
</x-app-layout>
