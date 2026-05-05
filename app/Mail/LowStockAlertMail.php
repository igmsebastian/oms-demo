<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Product $product,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Low Stock Alert: {$this->product->sku}");
    }

    public function content(): Content
    {
        return new Content(htmlString: sprintf(
            '<h1>Low Stock Alert</h1><p>%s has %d units remaining.</p>',
            e($this->product->name),
            $this->product->stock_quantity,
        ));
    }
}
