<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Merchant;
use App\Models\Affiliate;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MerchantHttpTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    protected Merchant $merchant;

    public function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()
            ->for(User::factory())
            ->create();

    }

    public function test_get_status()
    {
        $from = now()->subDay();
        $to = now();

        $orders = Order::factory()
            ->for($this->merchant)
            ->for(Affiliate::factory()->for($this->merchant)->for(User::factory()))
            ->count(10)
            ->create([
                'created_at' => now()->subHour()
            ]);

        $old = tap($orders->random())->update([
            'created_at' => now()->subWeek()
        ]);

        $future = tap($orders->whereNotIn('id', [$old->id])->random())->update([
            'created_at' => now()->addWeek()
        ]);

        $noAffiliate = tap($orders->whereNotIn('id', [$old->id, $future->id])->random())->update([
            'affiliate_id' => null
        ]);

        $between = $orders->whereBetween('created_at', [$from, $to]);

        $response = $this->actingAs($this->merchant->user)
            ->json('GET', route('merchant.order-stats'), compact('from', 'to'));


        $this->assertEquals($between->count(), $response['count']);
        $this->assertEquals($between->sum('subtotal'), $response['revenue']);
        $this->assertEquals($between->sum('commission_owed'), $response['commission_owed']);
    }
}
