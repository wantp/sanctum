<?php

namespace Laravel\Sanctum;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class Guard
{
    /**
     * The authentication factory implementation.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * The number of minutes tokens should be allowed to remain valid.
     *
     * @var int
     */
    protected $expiration;

    /**
     * The provider name.
     *
     * @var string
     */
    protected $provider;

    /**
     * Create a new guard instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @param  int  $expiration
     * @param  string  $provider
     * @return void
     */
    public function __construct(AuthFactory $auth, $expiration = null, $provider = null)
    {
        $this->auth = $auth;
        $this->expiration = $expiration;
        $this->provider = $provider;
    }

    /**
     * Retrieve the authenticated user for the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  UserProvider  $userProvider
     * @return mixed
     */
    public function __invoke(Request $request, $userProvider)
    {
        if ($user = $this->auth->guard(config('sanctum.guard', 'web'))->user()) {
            return $this->supportsTokens($user)
                        ? $user->withAccessToken(new TransientToken)
                        : $user;
        }

        if ($token = $request->bearerToken()) {
            $accessToken = Sanctum::personalAccessTokenModelProvider()->findToken($token);

            if (! $accessToken ||
                ($this->expiration &&
                 $accessToken->created_at->lte(now()->subMinutes($this->expiration)))
            ) {
                return;
            }

            $tokenable = $userProvider
                ? $userProvider->setModel($accessToken->tokenable_type)->retrieveById($accessToken->tokenable_id)
                : $accessToken->tokenable;;

            if(!$this->hasValidProvider($tokenable)){
                return;
            }

            return $this->supportsTokens($tokenable) ? $tokenable->withAccessToken(
                Sanctum::personalAccessTokenModelProvider()->updateAccessTokenLastUsedAt($accessToken)
            ) : null;
        }
    }

    /**
     * Determine if the tokenable model supports API tokens.
     *
     * @param  mixed  $tokenable
     * @return bool
     */
    protected function supportsTokens($tokenable = null)
    {
        return $tokenable && in_array(HasApiTokens::class, class_uses_recursive(
            get_class($tokenable)
        ));
    }

    /**
     * Determine if the tokenable model matches the provider's model type.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $tokenable
     * @return bool
     */
    protected function hasValidProvider($tokenable)
    {
        if (is_null($this->provider)) {
            return true;
        }

        $model = config("auth.providers.{$this->provider}.model");

        return $tokenable instanceof $model;
    }
}
