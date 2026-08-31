<?php

namespace App\Http\Controllers\Vasws;

use App\Http\Controllers\Controller;
use App\Support\ServiceStore;
use Illuminate\Http\JsonResponse;

class DisplayAllController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): JsonResponse
    {
        $data = collect(ServiceStore::all())->values()->map(fn (ServiceStore $service) => [
            'id' => $service->id,
            'englishname' => $service->englishName,
            'arabicname' => $service->arabicName,
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'successful operation',
            'data' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
