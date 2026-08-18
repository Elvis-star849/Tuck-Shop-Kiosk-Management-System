<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        try {
            $overrides = Setting::query()->pluck('value', 'key');
            foreach ($overrides as $key => $value) {
                if (str_starts_with((string) $key, 'company.')) {
                    config([$key => $value]);
                }
            }
        } catch (Throwable) {
            // Database may not be migrated yet.
        }
    }
}
