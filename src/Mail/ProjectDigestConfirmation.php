<?php

namespace RayzenAI\ProjectManagement\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use RayzenAI\ProjectManagement\Models\ProjectDigestSubscriber;

class ProjectDigestConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ProjectDigestSubscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm your Netajee plan digest subscription',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.plan-digest-confirmation',
            with: [
                'confirmUrl' => $this->subscriber->confirmUrl(),
                'unsubscribeUrl' => $this->subscriber->unsubscribeUrl(),
            ],
        );
    }
}
