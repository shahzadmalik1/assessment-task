<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Merchant;
use App\Models\Affiliate;
use App\Jobs\PayoutOrderJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {
        $dataUser = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['api_key'],
            'type' => User::TYPE_MERCHANT,
        ]);

        $merchant = new Merchant([
            'user_id' => $dataUser->id,
            'display_name' => $dataUser->name,
            'domain' => $data['domain']
        ]);

        $merchant->save();

        return $merchant;
    }


    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        $dataUser = User::updateOrCreate(['user_id' => $user->id], [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['api_key'],
            'type' => User::TYPE_MERCHANT,
        ]);

        $merchant = Merchant::latest()->first()->update([
            'user_id' => $dataUser->id,
            'display_name' => $data['name'],
            'domain' => $data['domain']
        ]);

        return $merchant;
    }

    /**
     * Find a merchant by their email.
     * Hint: You'll need to look up the user first.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            return Merchant::where('user_id', $user->id)->first();
        }
        return null;
    }

    /**
     * Pay out all of an affiliate's orders.
     * Hint: You'll need to dispatch the job for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        $unpaidOrders = $affiliate->orders()->where('status', 'unpaid')->get();

        foreach ($unpaidOrders as $order) {
            $order->update(['status' => 'paid']);
            dispatch(new PayoutOrderJob($order));
        }
    }
}
