+++
title =  "A Faktory Client in PHP"
date =  2018-06-18T00:00:00-07:00
tags = ["php"]
draft = false
+++

[Faktory](http://contribsys.com/faktory/) is a worker server created by Mike Perham, the same person who created
[Sidekiq](https://sidekiq.org/). His aim in developing the tool is to bring the same best practices hashed out by
Sidekiq to languages besides Ruby. Faktory is still in a pre-release phase (at the time of writing this post it is at
0.7.0). It might not yet be ready for primetime, but I decided to give it a try anyway.

Below follows a description of how Faktory works and how I went about implementing the library, noting any important
decisions. I am working on a project that uses this library and will write up on that experience once
it is completed. The full implementation can be found [here](https://github.com/camuthig/faktory-client-php). This
project is not production ready at this time.

## How It Works

At a high level, a developer needs two pieces to work with Faktory: a producer and a consumer.

The producer is the simple part of the equation. The producer connects to the server, authenticating as needed, and
begins pushing jobs to the server. In the Faktory protocol, jobs are called work units, and they have a few required
fields:

* A job ID
* A job type
* A list of arguments

On top of these required fields there are also optional fields, like `queue`, `priority`, `retry` etc. The list of
arguments provided in the work unit is just that: an exact map of arguments that are passed into a "handler" when
executing the work unit. This means that they should be simple scalars and 0-indexed.

The consumer is a bit more complex. When connecting to the server as a consumer, a number of extra fields can be
provided to identify the worker, including a worker ID, a hostname, a process identifier, and an array of labels.
Consumers work as long running processes to pull work units from the Faktory server and execute them. Consumers
must also register heartbeats with the server, to check for any updated status information from the server, as the
server can request the worker to terminate or go quiet at any time.

## Implementation Details


First, to best support reuse and testing in projects using the library, I designed the library around two main
interfaces: a `ProducerInterface` and a `ConsumerInterface`. The `Client`, `ProducerInterface` and `ConsumerInterface`
define/implement the commands defined in each of the [client, producer and consumer](https://github.com/contribsys/faktory/blob/master/docs/protocol-specification.md#client-commands)
sections of the protocol.

The `Client` defines the bulk of the library's logic, implementing the actual communication to and parsing of data
from the server. The Faktory protocol uses the Redis `RESP` encoding for all responses, and as such I have reused
code from the [predis](https://github.com/nrk/predis) library to simplify implementation. Most of the logic of the library
is nested within this class, as the core of what we are trying to accomplish is communicating with the Faktory server.
Implemenations of the two interfaces are provided that accept instances of a `Client` on construction to process messages.

The Faktory protocol defines messages as "work units", and as such, a `WorkUnit` class is defined within the project. Most
developers are probably used to seeing this called something more along the lines of a "job", but I believe it is
important to work within the defined protocol of a tool like Faktory to make understanding correlations easier in
future development. Generally, this domain concept will be encapsulated by another worker library so should have
limited impact on users of the library.

Finally, I kept the library less opinionated by implementing a consumer but purposefully avoiding implementing
a "worker" pattern. The `examples/consumer.php` file demonstrates how this _could_ be done, but I believe that the
worker pattern can take on many forms and is often a by-product of other libraries and frameworks in use. I'm working
on an example of this, and will follow up as I have finished it.

## Examples

The examples are taken from the `examples` directory of the project.

Producing messages:

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

$connection = new \Camuthig\Faktory\Client('tcp://127.0.0.1', 7419);

$producer = new \Camuthig\Faktory\Producer($connection);

while (true) {
    $id = uniqid();
    echo "Pushing job with ID $id\n";
    $producer->push(new \Camuthig\Faktory\WorkUnit($id, 'example', []));
    sleep(1);
}
```

Consuming messages:
```php
<?php

declare(ticks=1);

require __DIR__ . '/../vendor/autoload.php';

$connection = new \Camuthig\Faktory\Client('127.0.0.1', 7419, [
    'wid' => uniqid(),
    'labels' => ['php'],
]);

$consumer = new \Camuthig\Faktory\Consumer($connection);

$status = null;
$interrupt = null;

pcntl_signal(SIGINT, function ($signo, $signinfo) use (&$interrupt) {
    $interrupt = true;
});

while (true) {
    if ($interrupt) {
        echo "Stopping consumer...\n";
        $consumer->end();
        exit(0);
    }

    if ($status === \Camuthig\Faktory\ConsumerInterface::TERMINATE) {
        echo "Server requested consumer termination.\n";
        exit(0);
    } elseif ($status === \Camuthig\Faktory\ConsumerInterface::QUIET) {
        echo "Server requested consumer to go quiet.\n";
    } else {
        $workUnit = $consumer->fetch();

        if (!$workUnit) {
            sleep(5);
            continue;
        }

        echo "Received work unit " . $workUnit->getJobId() . "\n";
        $consumer->ack($workUnit);
    }

    sleep(1);

    $status = $consumer->beat();
}
```

## Improvements

The project could use some improvements still, including:

* Fixing a bug related to stopping workers and the connection closing while handling the `END` command
* Reconnecting to the server on a TCP exception
* Supporting authentication to the Faktory server
* Tests. I considered writing some unit tests, but realized they would be nearly useless without testing the
`Client` implementation. I'm still considering some functional tests using some kind of command line tool to send
commands.
