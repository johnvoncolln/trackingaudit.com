<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Tracker;
use App\Services\Tracking\EasyPostTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EasyPostWebhookController extends Controller
{
    public function __construct(protected EasyPostTracker $easyPostTracker)
    {
        //
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();
        $description = (string) ($payload['description'] ?? '');

        if (! str_starts_with($description, 'tracker.')) {
            return response()->json(['received' => true]);
        }

        $trackerPayload = $payload['result'] ?? null;

        if (! is_array($trackerPayload)) {
            Log::warning('easypost.webhook.missing_result', ['description' => $description]);

            return response()->json(['received' => true]);
        }

        $trackingCode = $trackerPayload['tracking_code'] ?? null;
        $easypostId = $trackerPayload['id'] ?? null;

        $tracker = Tracker::query()
            ->where('tracking_number', $trackingCode)
            ->orWhere('easypost_id', $easypostId)
            ->first();

        if (! $tracker) {
            Log::info('easypost.webhook.unknown_tracking_code', [
                'tracking_code' => $trackingCode,
                'trk_id' => $easypostId,
                'description' => $description,
            ]);

            return response()->json(['received' => true]);
        }

        $this->easyPostTracker->applyPayload($tracker, $trackerPayload);

        return response()->json(['received' => true]);
    }
}
