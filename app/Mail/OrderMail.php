<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

abstract class OrderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine(),
        );
    }

    public function content(): Content
    {
        return new Content(htmlString: sprintf(
            '<h1>%s</h1><p>%s</p><p>Order number: <strong>%s</strong></p>',
            e($this->title()),
            e($this->message()),
            e($this->order->order_number),
        ));
    }

    abstract protected function title(): string;

    abstract protected function message(): string;

    protected function subjectLine(): string
    {
        return sprintf('%s: %s', $this->title(), $this->order->order_number);
    }
}
