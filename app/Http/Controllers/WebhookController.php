<?php

namespace App\Http\Controllers;

use App\Services\AffiliateService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Pass the necessary data to the process order method
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $order = $this->orderService->processOrder($request->toArray());

            // Assuming $this->orderService->processOrder returns the processed order
            return new JsonResponse(['message' => 'Order processed successfully', 'order' => $order], 200);
        } catch (\Exception $e) {
            // Handle any exceptions that may occur during order processing
            return new JsonResponse(['error' => 'Order processing failed', 'message' => $e->getMessage()], 500);
        }
    }
}
