<?php

namespace App\Services;

use App\Contracts\Repositories\OrderActivityRepositoryInterface;
use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\User;

class OrderActivityService
{
    public function __construct(
        protected OrderActivityRepositoryInterface $activities,
    ) {}

    public function record(Order $order, string|OrderActivityEvent $event, array $data = []): OrderActivity
    {
        $actor = $data['actor'] ?? null;

        return $this->activities->create([
            'order_id' => $order->id,
            'actor_id' => $actor instanceof User ? $actor->id : ($data['actor_id'] ?? null),
            'actor_role' => $data['actor_role'] ?? ($actor instanceof User ? $actor->role : null),
            'event' => $event instanceof OrderActivityEvent ? $event : $event,
            'title' => $data['title'] ?? $this->defaultTitle($event),
            'description' => $data['description'] ?? null,
            'from_status' => $this->statusValue($data['from_status'] ?? null),
            'to_status' => $this->statusValue($data['to_status'] ?? null),
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    protected function defaultTitle(string|OrderActivityEvent $event): string
    {
        $value = $event instanceof OrderActivityEvent ? $event->value : $event;

        return str($value)->replace('_', ' ')->headline()->toString();
    }

    protected function statusValue(mixed $status): ?int
    {
        if ($status instanceof OrderStatus) {
            return $status->value;
        }

        return is_numeric($status) ? (int) $status : null;
    }
}
