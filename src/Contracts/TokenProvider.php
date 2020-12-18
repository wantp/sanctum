<?php

namespace Laravel\Sanctum\Contracts;

interface TokenProvider
{
    /**
     * Find the token instance matching the given token.
     *
     * @param  string  $token
     * @return static|null
     */
    public function findToken($token);

    /**
     * Update the token instance last used at.
     *
     * @param  $accessToken
     * @return $accessToken
     */
    public function updateAccessTokenLastUsedAt($accessToken);
}
