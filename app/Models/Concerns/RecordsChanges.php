<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait RecordsChanges
{
    public static function bootRecordsChanges(): void
    {
        static::created(function (Model $model): void {
            static::writeChangeLog($model, 'created');
        });

        static::updated(function (Model $model): void {
            static::writeChangeLog($model, 'updated');
        });

        static::deleted(function (Model $model): void {
            static::writeChangeLog($model, 'deleted');
        });
    }

    /**
     * @return list<string>
     */
    protected static function hiddenFromChangeLog(): array
    {
        return ['password', 'remember_token', 'payload'];
    }

    protected static function writeChangeLog(Model $model, string $event): void
    {
        if (! AuditLog::$enabled) {
            return;
        }

        $actor = Auth::user()?->name ?? 'System';
        $type = strtolower(class_basename($model));
        $label = static::changeLogLabel($model);

        if ($event === 'updated') {
            $changes = $model->getChanges();
            unset($changes['updated_at']);
            foreach (static::hiddenFromChangeLog() as $hidden) {
                unset($changes[$hidden]);
            }

            if ($changes === []) {
                return;
            }

            foreach ($changes as $field => $new) {
                $old = $model->getOriginal($field);
                AuditLog::record(
                    $type.'.updated',
                    $actor.' changed '.$type.' '.$label.' '.str_replace('_', ' ', $field).' from '.static::changeLogValue($old).' to '.static::changeLogValue($new),
                    $model,
                    $field,
                    $old,
                    $new,
                );
            }

            return;
        }

        AuditLog::record(
            $type.'.'.$event,
            $actor.' '.$event.' '.$type.' '.$label,
            $model,
        );
    }

    protected static function changeLogLabel(Model $model): string
    {
        foreach (['name', 'sale_number', 'return_number', 'invoice_number', 'purchase_number', 'description', 'key', 'email', 'reference'] as $attribute) {
            $value = $model->getAttribute($attribute);
            if (filled($value)) {
                return (string) $value;
            }
        }

        return '#'.$model->getKey();
    }

    protected static function changeLogValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'empty';
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if (is_array($value)) {
            $value = json_encode($value);
        }

        $text = (string) $value;

        return mb_strlen($text) > 120 ? mb_substr($text, 0, 117).'...' : $text;
    }
}
