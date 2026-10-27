<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AddressController extends Controller
{
    public function provinces()
    {
        return response()->json($this->fetch('provinces', 'provinces'));
    }

    public function wards(string $provinceCode)
    {
        abort_unless(preg_match('/^\d{2}$/', $provinceCode), 404);

        return response()->json($this->fetch("provinces/{$provinceCode}/communes", 'communes'));
    }

    private function fetch(string $path, string $key): array
    {
        try {
            return Cache::remember('vietnam-address:'.$path, now()->addDay(), function () use ($path, $key) {
                $response = Http::acceptJson()->timeout(10)->retry(2, 200)
                    ->get(rtrim(config('services.vietnam_address.url'), '/').'/'.$path)
                    ->throw();

                return collect($response->json($key, []))
                    ->map(fn (array $item) => ['code' => $item['code'], 'name' => $item['name']])
                    ->values()->all();
            });
        } catch (ConnectionException $exception) {
            abort(503, 'Dịch vụ địa chỉ đang tạm thời gián đoạn.');
        }
    }
}
