<?php

namespace App\Services;

use App\Enums\ClientPackageStatus;
use App\Exceptions\ActivePackageExistsException;
use App\Models\Client;
use App\Models\ClientPackage;
use App\Models\Package;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ClientPackageService
{
    /**
     * @param  array{
     *     package_id: int,
     *     purchased_sessions?: int,
     *     price_paid?: string|int|float,
     *     starts_at?: string|\DateTimeInterface|null,
     *     expires_at?: string|\DateTimeInterface|null
     * }  $data
     */
    public function assign(Client $client, array $data): ClientPackage
    {
        return DB::transaction(function () use ($client, $data) {
            $active = ClientPackage::query()
                ->where('client_id', $client->id)
                ->active()
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if ($active !== null && $active->remaining_sessions > 0) {
                throw new ActivePackageExistsException($active->remaining_sessions);
            }

            if ($active !== null && $active->remaining_sessions === 0) {
                $active->update(['status' => ClientPackageStatus::Completed]);
            }

            $package = Package::query()->findOrFail($data['package_id']);

            if (! $package->is_active) {
                throw new InvalidArgumentException('Only active packages can be assigned.');
            }

            return ClientPackage::query()->create([
                'client_id' => $client->id,
                'package_id' => $package->id,
                'package_name' => $package->name,
                'purchased_sessions' => (int) ($data['purchased_sessions'] ?? $package->sessions_count),
                'used_sessions' => 0,
                'price_paid' => $data['price_paid'] ?? $package->price,
                'starts_at' => $data['starts_at'] ?? now()->toDateString(),
                'expires_at' => $data['expires_at'] ?? null,
                'status' => ClientPackageStatus::Active,
            ]);
        });
    }
}
