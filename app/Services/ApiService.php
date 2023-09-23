<?php

namespace App\Services;

use RuntimeException;
use App\Models\Merchant;
use App\Models\Affiliate;
use Illuminate\Support\Str;
use App\Mail\AffiliateCreated;
use App\Services\AffiliateService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * You don't need to do anything here. This is just to help
 */
class ApiService
{
    /**
     * Create a new discount code for an affiliate
     *
     * @param Merchant $merchant
     *
     * @return array{id: int, code: string}
     */
    public function createDiscountCode(Merchant $merchant): array
    {
        return [
            'id' => rand(0, 100000),
            'code' => Str::uuid()
        ];
    }

    /**
     * Send a payout to an email
     *
     * @param  string $email
     * @param  float $amount
     * @return void
     * @throws RuntimeException
     */
    public function sendPayout(string $email, float $amount)
    {
        try {
            $affiliate = AffiliateService::getAffiliate($email);

            foreach($affiliate->orders as $order){
                $order->payout_status = Order::STATUS_PAID;
                $order->save();
            }

            Mail::to($email)->send(new AffiliateCreated($affiliate));
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
