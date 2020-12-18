<p align="center"><img src="https://laravel.com/assets/img/components/logo-sanctum.svg"></p>

<p align="center">
<a href="https://github.com/laravel/sanctum/actions"><img src="https://github.com/laravel/sanctum/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/sanctum"><img src="https://img.shields.io/packagist/dt/laravel/sanctum" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/sanctum"><img src="https://img.shields.io/packagist/v/laravel/sanctum" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/sanctum"><img src="https://img.shields.io/packagist/l/laravel/sanctum" alt="License"></a>
</p>

## Introduction

Laravel Sanctum provides a featherweight authentication system for SPAs and simple APIs.

## Official Documentation

Documentation for Sanctum can be found on the [Laravel website](https://laravel.com/docs/master/sanctum).

## Contributing

Thank you for considering contributing to Sanctum! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/sanctum/security/policy) on how to report security vulnerabilities.

## License

Laravel Sanctum is open-sourced software licensed under the [MIT license](LICENSE.md).


## Usage

### Install

add the repositories to `composer.json`
```
    "repositories": {
        "laravel/sanctum": {
            "type": "git",
            "url": "https://github.com/wantp/sanctum.git"
        }
    },
```


### Custom Token Provider

create a custom token provider like this

`app/Extensions/PersonalAccessTokenProvider.php`
```
<?php

namespace App\Extensions;

use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Contracts\TokenProvider;
use Laravel\Sanctum\Sanctum;

class PersonalAccessTokenProvider implements TokenProvider
{

    protected $cacheByTokenPrefix = 'sanctum:token:';

    protected $cacheByIdPrefix = 'santum:id:';

    public function findToken($token)
    {
        if (strpos($token, '|') === false) {
            return Cache::remember($this->cacheByTokenPrefix . $token, 3600, function () use ($token) {
                return Sanctum::personalAccessTokenModel()::where('token', hash('sha256', $token))->first();
            });
        }

        [$id, $token] = explode('|', $token, 2);

        $instance = Cache::remember($this->cacheByIdPrefix . $id, 3600, function () use ($id) {
            return Sanctum::personalAccessTokenModel()::find($id);
        });

        if ($instance) {
            return hash_equals($instance->token, hash('sha256', $token)) ? $instance : null;
        }

        return null;
    }

    public function updateAccessTokenLastUsedAt($accessToken)
    {
        return $accessToken;
    }
}
```

set provider for Sanctum in AuthServiceProvider

`app/Providers/AuthService.php`
```
    public function boot()
    {
        $this->registerPolicies();

        Sanctum::setPersonalAccessTokenModelProvider(app(PersonalAccessTokenProvider::class));
    }
```

### Custom User Provider

`app/Extensions/PersonalAccessTokenProvider.php`
```
<?php

namespace App\Extensions;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

class UserProvider extends EloquentUserProvider
{

    protected $cachePrefix = 'user:info:';

    protected $ttl = 3600;

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param mixed $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        return Cache::remember($this->cachePrefix . $identifier, $this->ttl, function () use ($identifier) {
            return parent::retrieveById($identifier);
        });
    }
}
```

set provider in AuthServiceProvider

`app/Providers/AuthService.php`
```
    public function boot()
    {
        $this->registerPolicies();

        Auth::provider('cache', function ($app, array $config) {
            return $app->make(UserProvider::class,['model' => $config['model']]);
        });
    }
```

config sanctum user provider

`config/auth.php`
```
    'guards' => [
         ...
        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'user.cache',
        ],
    ],
    
    'providers' => [
         'user.cache' => [
             'driver' => 'cache',
             'model' => App\Models\User::class,
         ],
    ],
```
