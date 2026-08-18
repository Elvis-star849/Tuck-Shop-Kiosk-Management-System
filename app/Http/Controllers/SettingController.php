<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', [
            'settings' => [
                'name' => Setting::get('company.name', config('company.name')),
                'tagline' => Setting::get('company.tagline', config('company.tagline')),
                'address' => Setting::get('company.address', config('company.address')),
                'email' => Setting::get('company.email', config('company.email')),
                'phone' => Setting::get('company.phone', config('company.phone')),
                'currency' => Setting::get('company.currency', config('company.currency')),
                'currency_symbol' => Setting::get('company.currency_symbol', config('company.currency_symbol')),
                'default_tax_rate' => Setting::get('company.default_tax_rate', config('company.default_tax_rate')),
                'receipt_footer' => Setting::get('company.receipt_footer', 'Thank you'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'currency' => ['required', 'string', 'max:8'],
            'currency_symbol' => ['required', 'string', 'max:8'],
            'default_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'receipt_footer' => ['nullable', 'string', 'max:200'],
        ]);

        foreach ($data as $key => $value) {
            Setting::put('company.'.$key, $value);
            config(['company.'.$key => $value]);
        }

        AuditLog::record('settings.updated', 'Admin updated business and receipt settings');

        return back()->with('success', 'Settings saved.');
    }
}
