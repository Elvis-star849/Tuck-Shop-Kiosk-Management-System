<x-app-layout>
    <x-slot name="header">{{ $supplier->exists ? 'Edit supplier' : 'New supplier' }}</x-slot>
    <x-slot name="subtitle">Contact details for stock purchases</x-slot>
    <x-slot name="title">{{ $supplier->exists ? 'Edit supplier' : 'New supplier' }}</x-slot>

    <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}" class="card card-pad">
        @csrf
        @if ($supplier->exists)
            @method('PUT')
        @endif
        <div class="form-grid">
            <div>
                <label class="field-label" for="name">Name</label>
                <input class="field" id="name" name="name" value="{{ old('name', $supplier->name) }}" required>
            </div>
            <div>
                <label class="field-label" for="contact_name">Contact person</label>
                <input class="field" id="contact_name" name="contact_name" value="{{ old('contact_name', $supplier->contact_name) }}">
            </div>
            <div>
                <label class="field-label" for="email">Email</label>
                <input class="field" id="email" type="email" name="email" value="{{ old('email', $supplier->email) }}">
            </div>
            <div>
                <label class="field-label" for="phone">Phone</label>
                <input class="field" id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}">
            </div>
            <div class="full">
                <label class="field-label" for="address">Address</label>
                <textarea class="field" id="address" name="address" rows="3">{{ old('address', $supplier->address) }}</textarea>
            </div>
        </div>
        <div class="actions" style="margin-top:18px;">
            <button class="btn btn-primary" type="submit">Save supplier</button>
            <a class="btn btn-ghost" href="{{ route('suppliers.index') }}">Cancel</a>
        </div>
    </form>
</x-app-layout>
