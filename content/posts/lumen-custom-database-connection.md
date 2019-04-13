---
title: "Creating Custom Database Connections in Lumen"
tags: ["php", "laravel", "lumen"]
date: 2019-04-17
---

A friend recently came to me with a question on how to create a database connection in Lumen without
using using external services to define the configuration values of the connection. I made some suggestions,
but it was an interesting quesiton so I decided to try tackling a [sample myself as well](https://github.com/camuthig/lumen-custom-db-connection).

I am not suggesting that the pattern described below for retrieving configuration values is a good idea. However,
sometimes as developers we are required to work within certain constraints we cannot control. That is just part
of the job, and it is important to be able to accomplish our work all the same.

Based on other architectural decisions, my friend's team had two different patterns for retrieving configuration values.
The first was that most configuration values, things like connection URLs for a database that might commonly be in a
`.env` or in environment variables, were stored in Redis to allow many applications to share the same values. The
second constraint was that credentials, like the username and password for connecting to a database, were stored
within an external service that could be accessed via HTTP. So the goal is to create our database connection using
a combination of these two services. In my example I will use Redis as the store for both services because
I don't want to take the time to implement a second HTTP service. However, the same encapsulations can be used.

The first step is to implement simple services that allow for setting and retrieving our two types of configuration
values.

```php
// https://github.com/camuthig/lumen-custom-db-connection/blob/master/app/Services/DistributedConfiguration.php
<?php
namespace App\Services;

use Predis\ClientInterface;

class DistributedConfiguration
{
    public function get(string $config, $default = null)
    {
        /** @var ClientInterface $redis */
        $redis = app('redis');
        return $redis->get($config) ?? $default;
    }

    public function set(string $config, $value): void
    {
        /** @var ClientInterface $redis */
        $redis = app('redis');
        $redis->set($config, $value);
    }
}
```

```php
// https://github.com/camuthig/lumen-custom-db-connection/blob/master/app/Services/Credentials.php
<?php

namespace App\Services;

use Predis\ClientInterface;

/**
 * A sample class that pulls credentials from something besides our local configurations.
 *
 * In reality this might be a HTTP call to some other service or pulling it from some encrypted source. The details of
 * that really aren't important, just that we have to pull these values from somewhere besides the local configurations
 * and the DistributedConfiguration.
 */
class Credentials
{
    public function get(string $key, $default = null)
    {
        /** @var ClientInterface $redis */
        $redis = app('redis');
        return $redis->get('secure.' . $key) ?? $default;
    }

    public function set(string $key, $value): void
    {
        /** @var ClientInterface $redis */
        $redis = app('redis');
        $redis->set('secure.' . $key, $value);
    }
}
```

With our simple serivces created, the next phase is to define logic allowing us to pull database connection configuration
values from them. The code leverages the work already done in the `Illuminate\Database\Connectors\ConnectionFactory` class
by extending it and just overriding the `parseConfig` function. The sample implementation is basic and only supports a `pgsql`
driver because that is what I was testing against, but the idea could be expanded to work with a more generic structure as
well.

```php
// https://github.com/camuthig/lumen-custom-db-connection/blob/master/app/Database/CustomConnectionFactory.php
<?php

declare(strict_types=1);

namespace App\Database;

use App\Services\Credentials;
use App\Services\DistributedConfiguration;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Connectors\ConnectionFactory;

class CustomConnectionFactory extends ConnectionFactory
{
    /**
     * @var DistributedConfiguration
     */
    private $configuration;

    /**
     * @var Credentials
     */
    private $credentials;

    public function __construct(Container $container, DistributedConfiguration $configuration, Credentials $credentials)
    {
        parent::__construct($container);
        $this->configuration = $configuration;
        $this->credentials = $credentials;
    }

    protected function parseConfig(array $config, $name)
    {
        return [
            'name' => $name,
            'driver' => 'pgsql',
            'prefix' => $this->configuration->get('database.prefix', ''),
            'host' => $this->configuration->get('database.host'),
            'port' => $this->configuration->get('database.port'),
            'database' => $this->configuration->get('database.database'),
            'username' => $this->credentials->get('database.username'),
            'password' => $this->credentials->get('database.password'),
            'charset' => $this->configuration->get('database.charset'),
            'schema' => $this->configuration->get('database.schema'),
        ];
    }
}
```

Next is to extend the `DatabaseManager` with a custom resolver for our connection, which we will name `custom`. I chose to
add this to a new service provider, to keep things clean, and then registered the service provider in `app.php`.

```php
// https://github.com/camuthig/lumen-custom-db-connection/blob/master/app/Providers/DatabaseConnectionServiceProvider.php
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Database\CustomConnectionFactory;
use Carbon\Laravel\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Application;

class DatabaseConnectionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        DB::extend('custom', function (array $config, ?string $name) {
            return app(CustomConnectionFactory::class)->make($config, $name);
        });
    }
}
```

Since this is lumen, and it does not include the `config` directly by default, we will then create the directory and copy the
`database.php` file into it, adding an empty `connections.custom` array. This is important because even though we are not
using any values from it, Lumen will expect the array to exist and fail if it does not.

And that is all there is to it. From here, we can
[add a basic a basic test route](https://github.com/camuthig/lumen-custom-db-connection/blob/master/routes/web.php#L18-L26)
and verify that we are able to connect to the database and make a query.
