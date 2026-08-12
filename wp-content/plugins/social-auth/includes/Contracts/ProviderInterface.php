<?php

namespace SocialAuth\Contracts;

interface ProviderInterface
{
    public function getName(): string;

    public function getAuthorizationUrl(string $state): string;

    /**
     * @return array<string, string>
     */
    public function fetchUserData(string $code): array;
}
