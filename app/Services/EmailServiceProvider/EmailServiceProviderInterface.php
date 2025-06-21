<?php

namespace App\Services\EmailServiceProvider;

interface EmailServiceProviderInterface
{
    /**
     * Get the provider slug identifier
     */
    public function getSlug(): string;

    /**
     * Get the human-readable provider name
     */
    public function getName(): string;

    /**
     * Check if an email is subscribed to the ESP
     */
    public function checkSubscriber(string $email): array;

    /**
     * Get all available tags from the ESP
     */
    public function getTags(): array;

    /**
     * Add a new subscriber to the ESP
     */
    public function addSubscriber(string $email, array $tags = []): array;

    /**
     * Add a tag to an existing subscriber
     */
    public function addTagToSubscriber(string $email, string $tagId): bool;

    /**
     * Create a new tag in the ESP
     */
    public function createTag(string $tagName): array;

    /**
     * Remove a tag from a subscriber
     */
    public function removeTagFromSubscriber(string $email, string $tagId): bool;

    /**
     * Test the connection to the ESP API
     */
    public function testConnection(): bool;

    /**
     * Validate the ESP configuration
     */
    public function validateConfiguration(array $credentials): array;

    /**
     * Get subscriber information including tags
     */
    public function getSubscriber(string $email): ?array;

    /**
     * Update subscriber information
     */
    public function updateSubscriber(string $email, array $data): bool;
} 