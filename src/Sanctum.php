<?php

namespace Laravel\Sanctum;

use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Contracts\TokenProvider;
use Mockery;

class Sanctum
{
    /**
     * The personal access client model class name.
     *
     * @var string
     */
    public static $personalAccessTokenModel = 'Laravel\\Sanctum\\PersonalAccessToken';

    /**
     * The personal access client provider instance.
     *
     * @var TokenProvider
     */
    protected static $personalAccessTokenProvider;

    /**
     * Indicates if Sanctum's migrations will be run.
     *
     * @var bool
     */
    public static $runsMigrations = true;

    /**
     * Set the current user for the application with the given abilities.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable|\Laravel\Sanctum\HasApiTokens $user
     * @param array $abilities
     * @param string $guard
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public static function actingAs($user, $abilities = [], $guard = 'sanctum')
    {
        $token = Mockery::mock(self::personalAccessTokenModel())->shouldIgnoreMissing(false);

        if (in_array('*', $abilities)) {
            $token->shouldReceive('can')->withAnyArgs()->andReturn(true);
        } else {
            foreach ($abilities as $ability) {
                $token->shouldReceive('can')->with($ability)->andReturn(true);
            }
        }

        $user->withAccessToken($token);

        if (isset($user->wasRecentlyCreated) && $user->wasRecentlyCreated) {
            $user->wasRecentlyCreated = false;
        }

        app('auth')->guard($guard)->setUser($user);

        app('auth')->shouldUse($guard);

        return $user;
    }

    /**
     * Set the personal access token model name.
     *
     * @param string $model
     * @return void
     */
    public static function usePersonalAccessTokenModel($model)
    {
        static::$personalAccessTokenModel = $model;
    }

    /**
     * Determine if Sanctum's migrations should be run.
     *
     * @return bool
     */
    public static function shouldRunMigrations()
    {
        return static::$runsMigrations;
    }

    /**
     * Configure Sanctum to not register its migrations.
     *
     * @return static
     */
    public static function ignoreMigrations()
    {
        static::$runsMigrations = false;

        return new static;
    }

    /**
     * Get the token model class name.
     *
     * @return string
     */
    public static function personalAccessTokenModel()
    {
        return static::$personalAccessTokenModel;
    }

    /**
     * @param TokenProvider $provider
     */
    public static function setPersonalAccessTokenModelProvider(TokenProvider $provider)
    {
        static::$personalAccessTokenProvider = $provider;
    }

    /**
     * @return TokenProvider|PersonalAccessTokenProvider
     */
    public static function personalAccessTokenModelProvider()
    {
        if (!static::$personalAccessTokenProvider) {
            static::usePersonalAccessTokenModelProvider(app(PersonalAccessTokenProvider::class));
        }

        return static::$personalAccessTokenProvider;
    }
}
