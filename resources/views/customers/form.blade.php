<x-app-layout>
    <x-slot name="header">{{ $customer->exists ? 'Edit customer' : 'New customer' }}</x-slot>
    <x-slot name="subtitle">Name, company, and contact details</x-slot>
    <x-slot name="title">{{ $customer->exists ? 'Edit customer' : 'New customer' }}</x-slot>

    <form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}" class="card card-pad">
        @csrf
        @if ($customer->exists)
            @method('PUT')
        @endif

        <div class="form-grid">
            <div>
                <label class="field-label" for="name">Contact name</label>
                <input class="field" id="name" name="name" value="{{ old('name', $customer->name) }}" required>
            </div>
            <div>
                <label class="field-label" for="company_name">Company</label>
                <input class="field" id="company_name" name="company_name" value="{{ old('company_name', $customer->company_name) }}">
            </div>
            <div>
                <label class="field-label" for="email">Email</label>
                <input class="field" id="email" type="email" name="email" value="{{ old('email', $customer->email) }}">
            </div>
            <div>
                <label class="field-label" for="phone">Phone</label>
                <input class="field" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}">
            </div>
            <div class="full">
                <label class="field-label" for="address">Address</label>
                <textarea class="field" id="address" name="address" rows="3">{{ old('address', $customer->address) }}</textarea>
            </div>
        </div>

        <div class="actions" style="margin-top:18px;">
            <button class="btn btn-primary" type="submit">Save customer</button>
            <a class="btn btn-ghost" href="{{ route('customers.index') }}">Cancel</a>
        </div>
    </form>
</x-app-layout>
