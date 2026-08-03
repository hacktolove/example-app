<?php

namespace App\Support;

class VasServiceCatalog
{
    /**
     * @return array<int, array{id: int, package: string, english_name: string, arabic_name: string}>
     */
    public static function all(): array
    {
        return collect(config('vasws.services', []))
            ->map(fn (array $service, int $id) => [...$service, 'id' => $id])
            ->values()
            ->all();
    }

    /**
     * @return array{id: int, package: string, english_name: string, arabic_name: string}|null
     */
    public static function find(int $id): ?array
    {
        $service = config("vasws.services.{$id}");

        return $service ? [...$service, 'id' => $id] : null;
    }

    /**
     * @return array{id: int, package: string, english_name: string, arabic_name: string}|null
     */
    public static function findByPackage(string $package): ?array
    {
        foreach (config('vasws.services', []) as $id => $service) {
            if ($service['package'] === $package) {
                return [...$service, 'id' => $id];
            }
        }

        return null;
    }
}
