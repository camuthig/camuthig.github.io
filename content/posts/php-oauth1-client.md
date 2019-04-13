---
title: "Easy Oauth1 Client in PHP"
date: 2018-06-29
tags: ["php"]
draft: false
---
I spent longer than I would have liked the other day finding a good way to
use OAuth1 client credentials to integrate with an API in PHP. In the end,
the solution was dead simple but not the first result on Packagist, so I
thought I would put my notes down here to help others.

The package I used was the [Guzzle oauth-subscriber](https://github.com/guzzle/oauth-subscriber).
For those not familiar, `subscriber` is a term that Guzzle uses to describe a
number of their middleware, which I was unaware of and caused me to skip over
it at first. The tool is very simple to use, as shown in the project's readme.

```php

<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

$stack = HandlerStack::create();

$middleware = new Oauth1([
    'consumer_key'    => 'my_key',
    'consumer_secret' => 'my_secret',
    'token'           => 'my_token',
    'token_secret'    => 'my_token_secret'
]);
$stack->push($middleware);

$client = new Client([
    'base_uri' => 'https://api.twitter.com/1.1/',
    'handler' => $stack,
    'auth' => 'oauth'
]);
```

And bam! You've got yourself OAuth1 client authentication. The great part of
this is that by putting it on your client, you can abstract away the knowledge
that this is even necessary, and it fits wonderfully into a DI pattern of injecting
a Guzzle `ClientInterface` into your classes.
