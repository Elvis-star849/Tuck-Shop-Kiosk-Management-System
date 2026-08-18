<x-app-layout>
    <x-slot name="header">Settings</x-slot>
    <x-slot name="subtitle">Business information, tax, and receipt text</x-slot>
    <x-slot name="title">Settings</x-slot>

    <form method="POST" action="{{ route('settings.update') }}" class="card card-pad" style="max-width:760px;">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div>
                <label class="field-label" for="name">Business name</label>
                <input class="field" id="name" name="name" value="{{ old('name', $settings['name']) }}" required>
            </div>
            <div>
                <label class="field-label" for="tagline">Tagline</label>
                <input class="field" id="tagline" name="tagline" value="{{ old('tagline', $settings['tagline']) }}">
            </div>
            <div class="full">
                <label class="field-label" for="address">Address</label>
                <input class="field" id="address" name="address" value="{{ old('address', $settings['address']) }}">
            </div>
            <div>
                <label class="field-label" for="email">Email</label>
                <input class="field" id="email" type="email" name="email" value="{{ old('email', $settings['email']) }}">
            </div>
            <div>
                <label class="field-label" for="phone">Phone</label>
                <input class="field" id="phone" name="phone" value="{{ old('phone', $settings['phone']) }}">
            </div>
            <div>
                <label class="field-label" for="currency">Currency</label>
                <input class="field" id="currency" name="currency" value="{{ old('currency', $settings['currency']) }}" required>
            </div>
            <div>
                <label class="field-label" for="currency_symbol">Symbol</label>
                <input class="field" id="currency_symbol" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol']) }}" required>
            </div>
            <div>
                <label class="field-label" for="default_tax_rate">Default tax rate (%)</label>
                <input class="field" id="default_tax_rate" type="number" step="0.01" min="0" max="100" name="default_tax_rate" value="{{ old('default_tax_rate', $settings['default_tax_rate']) }}" required>
            </div>
            <div class="full">
                <label class="field-label" for="receipt_footer">Receipt footer</label>
                <input class="field" id="receipt_footer" name="receipt_footer" value="{{ old('receipt_footer', $settings['receipt_footer']) }}">
            </div>
        </div>
        <div class="actions" style="margin-top:18px;">
            <button class="btn btn-primary" type="submit">Save settings</button>
        </div>
    </form>
</x-app-layout>
