<?php

namespace App\Mail;

use App\Models\EmailVerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public EmailVerificationRequest $verificationRequest;
    public string $verificationUrl;
    public ?string $utmContent;

    public function __construct(
        EmailVerificationRequest $verificationRequest,
        string $verificationUrl,
        ?string $utmContent = null
    ) {
        $this->verificationRequest = $verificationRequest;
        $this->verificationUrl = $verificationUrl;
        $this->utmContent = $utmContent;
    }

    public function envelope(): Envelope
    {
        $tenant = $this->verificationRequest->tenant;
        $content = $this->verificationRequest->content;
        $channelName = $tenant->ytChannel?->title ?? $tenant->name;

        return new Envelope(
            subject: "Verify your email to access: {$content->title}",
            from: config('mail.from.address', 'noreply@' . config('app.domain')),
            replyTo: $tenant->ytChannel?->custom_url ? ["noreply@{$tenant->ytChannel->custom_url}"] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-verification',
            with: [
                'verificationRequest' => $this->verificationRequest,
                'verificationUrl' => $this->verificationUrl,
                'tenant' => $this->verificationRequest->tenant,
                'content' => $this->verificationRequest->content,
                'channelName' => $this->verificationRequest->tenant->ytChannel?->title ?? $this->verificationRequest->tenant->name,
                'channelBanner' => $this->verificationRequest->tenant->ytChannel?->banner_image_url,
                'channelAvatar' => $this->verificationRequest->tenant->ytChannel?->thumbnail_url,
                'utmContent' => $this->utmContent,
                'expiresAt' => $this->verificationRequest->expires_at,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
} 