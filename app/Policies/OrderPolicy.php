<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || $order->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function confirm(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    public function requestCancellation(User $user, Order $order): bool
    {
        return $user->isAdmin() || $order->user_id === $user->id;
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    public function partiallyCancel(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    public function refund(User $user, Order $order): bool
    {
        return $user->isAdmin()
            || ($order->user_id === $user->id && $order->status === OrderStatus::Delivered);
    }
}
