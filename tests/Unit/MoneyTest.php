<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Support\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_amounts_are_formatted_in_fcfa(): void
    {
        $this->assertSame('8 000 FCFA', Money::format(8000));
        $this->assertSame('1 250 000 FCFA', Money::format('1250000.00'));
        $this->assertSame('500 FCFA', Money::format('500'));
        $this->assertSame('0 FCFA', Money::format(null));
    }

    public function test_decimal_arithmetic_has_no_float_errors(): void
    {
        $this->assertSame('0.30', Money::add('0.10', '0.20'));
        $this->assertSame('22500.00', Money::multiply('7500.00', 3));
    }

    public function test_order_status_transitions(): void
    {
        $this->assertTrue(OrderStatus::New->canTransitionTo(OrderStatus::Confirmed));
        $this->assertFalse(OrderStatus::New->canTransitionTo(OrderStatus::Delivered));
        $this->assertFalse(OrderStatus::Delivered->canTransitionTo(OrderStatus::Cancelled));
        $this->assertSame([], OrderStatus::Cancelled->allowedTransitions());
    }
}
