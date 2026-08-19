<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyShopSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $shop = $user?->shop;

        if ($shop) {
            config([
                'company.name' => $shop->name,
                'company.phone' => $shop->phone ?: config('company.phone'),
                'company.address' => $shop->address ?: config('company.address'),
            ]);

            $overrides = Setting::query()->pluck('value', 'key');
            foreach ($overrides as $key => $value) {
                if (str_starts_with((string) $key, 'company.')) {
                    config([$key => $value]);
                }
            }
        }

        return $next($request);
    }
}
