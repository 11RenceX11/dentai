<?php

namespace App\Http\Controllers;

use App\Services\PaymongoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** PayMongo calls this when a customer finishes paying. The signature proves it really came from PayMongo. */
class PaymongoWebhookController extends Controller
{
    public function __invoke(Request $request, PaymongoService $paymongo): JsonResponse
    {
        $raw = $request->getContent();

        abort_unless($paymongo->verifySignature($request->header('Paymongo-Signature'), $raw), 400, 'Bad signature');

        $event = json_decode($raw, true) ?: [];

        // The payload nests the event slightly differently across API versions, so read both shapes.
        $type = data_get($event, 'data.type') ?? data_get($event, 'data.attributes.type');
        $session = data_get($event, 'data.data') ?? data_get($event, 'data.attributes.data');

        if ($type === 'checkout_session.payment.paid' && is_array($session)) {
            $paymongo->recordPaidSession($session);
        }

        // Always answer 200 quickly, even for events we ignore (otherwise PayMongo keeps retrying).
        return response()->json(['received' => true]);
    }
}
