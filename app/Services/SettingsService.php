<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\SafeImageUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SettingsService
{
    /** @var array<string, array{value: ?string, type: ?string}>|null */
    protected ?array $items = null;

    public function get(string $key, mixed $default = null): mixed
    {
        if ($key === 'low_session_threshold') {
            $key = 'low_session_warning_threshold';
        }

        $this->load();

        if (! array_key_exists($key, $this->items ?? [])) {
            return $default;
        }

        return $this->cast($this->items[$key]['value'], $this->items[$key]['type']);
    }

    public function set(string $key, mixed $value, ?string $type = 'string'): void
    {
        if ($key === 'low_session_threshold') {
            $key = 'low_session_warning_threshold';
        }

        Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => is_scalar($value) || $value === null ? (string) $value : json_encode($value),
                'type' => $type,
            ],
        );

        $this->forget();
    }

    public function forget(): void
    {
        $this->items = null;
    }

    public function appName(): string
    {
        $name = trim((string) $this->get('app_name', 'LastRound'));

        return $name !== '' ? $name : 'LastRound';
    }

    public function logoPath(): ?string
    {
        $path = $this->get('logo');

        return is_string($path) && $path !== '' ? $path : null;
    }

    public function logoUrl(): ?string
    {
        $path = $this->logoPath();

        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function storeLogo(UploadedFile $file): string
    {
        $this->deleteLogoFile($this->logoPath());

        $path = $file->storeAs('branding', SafeImageUpload::filename($file), 'public');

        $this->set('logo', $path, 'string');

        return $path;
    }

    public function deleteLogo(): void
    {
        $this->deleteLogoFile($this->logoPath());
        $this->set('logo', '', 'string');
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function updateMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $type = in_array($key, ['default_session_duration', 'low_session_warning_threshold'], true)
                ? 'integer'
                : 'string';

            $this->set($key, $value ?? '', $type);
        }
    }

    protected function deleteLogoFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    protected function load(): void
    {
        if ($this->items !== null) {
            return;
        }

        $this->items = Setting::query()
            ->get(['key', 'value', 'type'])
            ->mapWithKeys(fn (Setting $setting) => [
                $setting->key => [
                    'value' => $setting->value,
                    'type' => $setting->type,
                ],
            ])
            ->all();
    }

    protected function cast(?string $value, ?string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer', 'int' => (int) $value,
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
