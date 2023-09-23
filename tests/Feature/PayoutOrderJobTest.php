<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Merchant;
use App\Models\Affiliate;
use App\Jobs\PayoutOrderJob;
use App\Services\ApiService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;


class PayoutOrderJobTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Order $order;

    public function setUp(): void
    {
        parent::setUp();

        $this->order = Order::factory()
            ->for($merchant = Merchant::factory()->for(User::factory())->create())
            ->for(Affiliate::factory()->for($merchant)->for(User::factory()))
            ->create();
    }

    public function test_calls_api()
    {
        try {
            $this->mock(ApiService::class)
            ->shouldReceive('sendPayout')
            ->once()
            ->with($this->order->affiliate->user->email, $this->order->commission_owed);

            dispatch(new PayoutOrderJob($this->order));

            $this->assertDatabaseHas('orders', [
                'id' => $this->order->id,
                'payout_status' => Order::STATUS_PAID
            ]);
        } catch (\Throwable $th) {
            $this->fail($th);
        }
    }

    public function test_rolls_back_if_exception_thrown()
    {
        $this->mock(ApiService::class)
            ->shouldReceive('sendPayout')
            ->once()
            ->with($this->order->affiliate->user->email, $this->order->commission_owed)
            ->andThrow(RuntimeException::class);

        $this->expectException(RuntimeException::class);

        dispatch(new PayoutOrderJob($this->order));

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'payout_status' => Order::STATUS_UNPAID
        ]);
    }
}
