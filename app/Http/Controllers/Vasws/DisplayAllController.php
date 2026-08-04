<?php

namespace App\Http\Controllers\Vasws;

use App\Http\Controllers\Controller;
use App\Support\VasServiceCatalog;
use Illuminate\Http\JsonResponse;

class DisplayAllController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): JsonResponse
    {
        $data = collect(VasServiceCatalog::all())->map(fn (array $service) => [
            'id' => $service['id'],
            'englishname' => $service['english_name'],
            'arabicname' => $service['arabic_name'],
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'successful operation',
            'data' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
