<?php

namespace Tests\Feature\Services;

use App\Constants\OrderStatus;
use App\Models\Currency;
use App\Models\OneTimeProduct;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTest;

class OrderServiceTest extends FeatureTest
{
    public function test_find_all_user_successful_orders(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $product1Slug = Str::random(10);
        $product1 = OneTimeProduct::factory()->create([
            'slug' => $product1Slug,
        ]);

        $order1 = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::SUCCESS->value,
        ]);

        $order1->items()->createMany([
            [
                'one_time_product_id' => $product1->id,
                'price' => 100,
                'currency' => Currency::find(1)->id,
                'quantity' => 1,
                'price_per_unit' => 100,
            ],
        ]);

        $product2Slug = Str::random(10);
        $product2 = OneTimeProduct::factory()->create([
            'slug' => $product2Slug,
        ]);

        $order2 = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::SUCCESS->value,
        ]);

        $order2->items()->createMany([
            [
                'one_time_product_id' => $product2->id,
                'price' => 100,
                'currency' => Currency::find(1)->id,
                'quantity' => 1,
                'price_per_unit' => 100,
            ],
        ]);

        $orderService = app()->make(OrderService::class);

        $orders = $orderService->findAllUserSuccessfulOrders($user);

        $this->assertCount(2, $orders);

        $this->assertEquals($order1->id, $orders[0]->id);
        $this->assertEquals($order2->id, $orders[1]->id);
    }

    public function test_find_all_user_ordered_products(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $product1Slug = Str::random(10);
        $product1 = OneTimeProduct::factory()->create([
            'slug' => $product1Slug,
        ]);

        $order1 = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::SUCCESS->value,
        ]);

        $order1->items()->createMany([
            [
                'one_time_product_id' => $product1->id,
                'price' => 100,
                'currency' => Currency::find(1)->id,
                'quantity' => 1,
                'price_per_unit' => 100,
            ],
        ]);

        $product2Slug = Str::random(10);
        $product2 = OneTimeProduct::factory()->create([
            'slug' => $product2Slug,
        ]);

        $order2 = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::SUCCESS->value,
        ]);

        $order2->items()->createMany([
            [
                'one_time_product_id' => $product2->id,
                'price' => 100,
                'currency' => Currency::find(1)->id,
                'quantity' => 1,
                'price_per_unit' => 100,
            ],
        ]);

        $orderService = app()->make(OrderService::class);

        $orderedProducts = $orderService->findAllUserOrderedProducts($user);

        $this->assertCount(2, $orderedProducts);

        $this->assertEquals($product1->slug, $orderedProducts[0]->slug);
        $this->assertEquals($product2->slug, $orderedProducts[1]->slug);
    }
}
