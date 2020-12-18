<?php

namespace Laravel\Sanctum;

use Laravel\Sanctum\Contracts\TokenProvider;
use Laravel\Sanctum\Sanctum;

class PersonalAccessTokenProvider implements TokenProvider
{
    /**
     * Find the token instance matching the given token.
     *
     * @param  string  $token
     * @return static|null
     */
    public function findToken($token)
    {
        return  Sanctum::$personalAccessTokenModel::findToken($token);
    }

    /**
     * Update the token instance last used at.
     *
     * @param  $accessToken
     * @return $accessToken
     */
    public function updateAccessTokenLastUsedAt($accessToken)
    {
        return tap($accessToken->forceFill(['last_used_at' => now()]))->save();
    }
}
