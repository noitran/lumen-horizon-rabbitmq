Lumen-Horizon-RabbitMQ
====================

<p align="center">
<a href="https://scrutinizer-ci.com/g/noitran/lumen-horizon-rabbitmq/code-structure"><img src="https://img.shields.io/scrutinizer/coverage/g/noitran/lumen-horizon-rabbitmq.svg?style=flat-square" alt="Coverage Status"></img></a>
<a href="https://scrutinizer-ci.com/g/noitran/lumen-horizon-rabbitmq"><img src="https://img.shields.io/scrutinizer/g/noitran/lumen-horizon-rabbitmq.svg?style=flat-square" alt="Quality Score"></img></a>
<a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square" alt="Software License"></img></a>
<a href="https://github.com/noitran/lumen-horizon-rabbitmq/releases"><img src="https://img.shields.io/github/release/noitran/lumen-horizon-rabbitmq.svg?style=flat-square" alt="Latest Version"></img></a>
<a href="https://packagist.org/packages/iocaste/lumen-horizon-rabbitmq"><img src="https://img.shields.io/packagist/dt/iocaste/lumen-horizon-rabbitmq.svg?style=flat-square" alt="Total Downloads"></img></a>
</p>

## About

Connector package for lumen that allows to use RabbitMQ with laravel's horizon dashboard. Uses [kinsolee/horizon-lumen](https://github.com/kinsolee/horizon-lumen) horizon fork that enables horizon support for lumen.

## Full Installation Guide

* Install as composer packages

```bash
$ composer require vladimir-yuldashev/laravel-queue-rabbitmq \
    kinsolee/horizon-lumen \
    noitran/lumen-horizon-rabbitmq
```

* Open your bootstrap/app.php and register all needed providers in specified sequence:

```php
$app->register(VladimirYuldashev\LaravelQueueRabbitMQ\LaravelQueueRabbitMQServiceProvider::class);
$app->register(Noitran\Lumen\Horizon\HorizonServiceProvider::class);
$app->register(Laravel\Horizon\HorizonServiceProvider::class);
```

* Copy queue.php config into `src/config/` directory

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Driver
    |--------------------------------------------------------------------------
    |
    | Laravel's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for each one. Here you may set the default queue driver.
    |
    | Supported: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'default' => env('QUEUE_CONNECTION', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'rabbitmq' => [
            'driver' => 'rabbitmq',
            'dsn' => env('RABBITMQ_DSN', null),

            /*
             * Could be one a class that implements \Interop\Amqp\AmqpConnectionFactory for example:
             *  - \Enqueue\AmqpExt\AmqpConnectionFactory if you install enqueue/amqp-ext
             *  - \Enqueue\AmqpLib\AmqpConnectionFactory if you install enqueue/amqp-lib
             *  - \Enqueue\AmqpBunny\AmqpConnectionFactory if you install enqueue/amqp-bunny
             */
            'factory_class' => Enqueue\AmqpExt\AmqpConnectionFactory::class,

            'host' => env('RABBITMQ_HOST', '127.0.0.1'),
            'port' => env('RABBITMQ_PORT', 5672),

            'vhost' => env('RABBITMQ_VHOST', '/'),
            'login' => env('RABBITMQ_LOGIN', 'user'),
            'password' => env('RABBITMQ_PASSWORD', 'bitnami'),

            'queue' => env('RABBITMQ_QUEUE', 'default'),

            'options' => [

                'exchange' => [
                    'name' => env('RABBITMQ_EXCHANGE_NAME'),

                    /*
                     * Determine if exchange should be created if it does not exist.
                     */
                    'declare' => env('RABBITMQ_EXCHANGE_DECLARE', true),

                    /*
                     * Read more about possible values at https://www.rabbitmq.com/tutorials/amqp-concepts.html
                     */
                    'type' => env('RABBITMQ_EXCHANGE_TYPE', \Interop\Amqp\AmqpTopic::TYPE_DIRECT),
                    'passive' => env('RABBITMQ_EXCHANGE_PASSIVE', false),
                    'durable' => env('RABBITMQ_EXCHANGE_DURABLE', true),
                    'auto_delete' => env('RABBITMQ_EXCHANGE_AUTODELETE', false),
                    'arguments' => env('RABBITMQ_EXCHANGE_ARGUMENTS'),
                ],

                'queue' => [
                    /*
                     * Determine if queue should be created if it does not exist.
                     */
                    'declare' => env('RABBITMQ_QUEUE_DECLARE', true),

                    /*
                     * Determine if queue should be binded to the exchange created.
                     */
                    'bind' => env('RABBITMQ_QUEUE_DECLARE_BIND', true),

                    /*
                     * Read more about possible values at https://www.rabbitmq.com/tutorials/amqp-concepts.html
                     */
                    'passive' => env('RABBITMQ_QUEUE_PASSIVE', false),
                    'durable' => env('RABBITMQ_QUEUE_DURABLE', true),
                    'exclusive' => env('RABBITMQ_QUEUE_EXCLUSIVE', false),
                    'auto_delete' => env('RABBITMQ_QUEUE_AUTODELETE', false),
                    'arguments' => env('RABBITMQ_QUEUE_ARGUMENTS'),
                ],
            ],

            /*
             * Determine the number of seconds to sleep if there's an error communicating with rabbitmq
             * If set to false, it'll throw an exception rather than doing the sleep for X seconds.
             */
            'sleep_on_error' => env('RABBITMQ_ERROR_SLEEP', 5),

            /*
             * Optional SSL params if an SSL connection is used
             * Using an SSL connection will also require to configure your RabbitMQ to enable SSL. More details can be
             * founds here: https://www.rabbitmq.com/ssl.html
             */

            'ssl_params' => [
                'ssl_on' => env('RABBITMQ_SSL', false),
                'cafile' => env('RABBITMQ_SSL_CAFILE', null),
                'local_cert' => env('RABBITMQ_SSL_LOCALCERT', null),
                'local_key' => env('RABBITMQ_SSL_LOCALKEY', null),
                'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),
                'passphrase' => env('RABBITMQ_SSL_PASSPHRASE', null),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],
];

```

* Copy horizon.php config from vendor directory into `src/config/` directory

* Be sure to change `connection` of your queues to `rabbitmq`. Example:

```php
    'environments' => [
        'production' => [
            'supervisor-1' => [
                'connection' => 'rabbitmq',
                'queue' => ['default'],
                'balance' => 'simple',
                'processes' => 10,
                'tries' => 3,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'connection' => 'rabbitmq',
                'queue' => ['default'],
                'balance' => 'simple',
                'processes' => 3,
                'tries' => 3,
            ],
        ],
    ],
```

---

Initial code idea goes to [designmynight](https://github.com/designmynight/laravel-horizon-rabbitmq)
