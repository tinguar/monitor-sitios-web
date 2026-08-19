<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SiteMonitor;
use App\Services\WhatsAppNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class WhatsAppTestController extends Controller
{
    public function send(Request $request, WhatsAppNotifier $notifier): JsonResponse
    {
        $kind = (string) $request->input('template', '');
        $countryCode = trim((string) $request->input('country_code', '593'));
        $phone = trim((string) $request->input('phone', ''));

        try {
            $result = $notifier->sendTest($countryCode, $phone, $kind);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['error' => 'No se pudo enviar la plantilla'], 500);
        }

        return response()->json([
            'ok' => true,
            'wamid' => $result['wamid'],
            'to' => $notifier->normalizePhone($countryCode, $phone),
            'template' => $kind,
        ]);
    }

    public function digestAll(SiteMonitor $monitor): JsonResponse
    {
        $monitor->runChecks();
        $result = $monitor->sendDigests();

        return response()->json(array_merge(
            ['ok' => $result['failed'] === 0 && $result['sent'] > 0],
            $result,
        ));
    }
}
