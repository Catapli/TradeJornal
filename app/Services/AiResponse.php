<?php

namespace App\Services;

final class AiResponse
{
    private function __construct(
        public readonly bool $ok,
        public readonly ?string $content = null,
        public readonly ?int $status = null,
        public readonly bool $truncated = false,
        public readonly bool $fromCache = false,
        public readonly ?string $error = null,
    ) {}

    public static function success(string $content): self
    {
        return new self(ok: true, content: $content);
    }

    public static function fromCache(string $content): self
    {
        return new self(ok: true, content: $content, fromCache: true);
    }

    public static function truncated(): self
    {
        return new self(ok: false, truncated: true);
    }

    public static function connectionError(string $message): self
    {
        return new self(ok: false, error: $message);
    }

    public static function httpError(int $status, string $message): self
    {
        return new self(ok: false, status: $status, error: $message);
    }

    /**
     * Mensaje traducido listo para mostrar en la UI.
     */
    public function userMessage(): string
    {
        if ($this->truncated) {
            return __('ai.errors.truncated');
        }

        return match ($this->status) {
            429 => __('ai.errors.rate_limited'),
            503 => __('ai.errors.unavailable'),
            null => __('ai.errors.connection'),
            default => __('ai.errors.generic', ['status' => $this->status]),
        };
    }
}
