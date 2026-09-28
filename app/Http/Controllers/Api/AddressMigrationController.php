<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AddressMigrationService;
use Illuminate\Http\JsonResponse;

class AddressMigrationController extends Controller
{
    public function __construct(
        protected AddressMigrationService $migrationService
    ) {
    }

    public function migrate(): JsonResponse
    {
        $report = $this->migrationService->migratePending();

        $statusCode = $report['failed'] === 0 ? 200 : 207;

        return response()->json([
            'message' => 'Pending legacy address migration batch completed.',
            'summary' => [
                'total_pending' => $report['total_pending'],
                'successful'    => $report['successful'],
                'failed'        => $report['failed'],
            ],
            'details' => $report['results'],
        ], $statusCode);
    }
}
