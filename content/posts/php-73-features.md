---
title: "PHP 7.3 Features I'm Excited About"
date: 2018-12-19
tags: ["php"]
draft: true
---

By the time this post is live, PHP 7.3 should already have dropped. Since the release of 7.0, I have found myself very
happy with the quality of work the core team has released. A lot of new language features have been released that focus
on deveoper productivity, which matters a lot when maintaining a large project. PHP 7.3 includes a number of great
features, but there are a few that I am especially excited about.

- [Throw on JSON Errors {#throw-on-json-error}](#throw-on-json-errors-throw-on-json-error)
- [Indent Aware Heredoc/Nowdoc {#indent-aware-docs}](#indent-aware-heredocnowdoc-indent-aware-docs)
- [Trailing Commas in Function/Method Calls {#trailing-commas-in-function-calls}](#trailing-commas-in-functionmethod-calls-trailing-commas-in-function-calls)

### Throw on JSON Errors {#throw-on-json-error}

[RFC](https://wiki.php.net/rfc/json_throw_on_error)

Parsing JSON is a common requirement for any API. Up to this point, the common pattern was to attempt to decode the
input, check for an error, and then proceed.

```php
<?php

$input = '{"invalid""json"}';

$json = json_decode($input);

if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception('Invalid JSON: ' . json_last_error_msg());
}

// Do things with my JSON
```

This wasn't an awful experience, but I'll be honest, I often forgot that it was needed at all and would have to go back
and add the check after I saw something go wrong. Now, we are presented a new option in the `JSON_THROW_ON_ERROR` option.
This allows developers to work in a standard `try/catch` pattern when parsing JSON.

```php
<?php

$input = '{"invalid""json"}';

try {
    json_decode($input, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    // Not really needed anymore, but you can handle custom logic here.
    throw new Exception('Invalid JSON: ' . $e->getMessage());
}

// Do things with my JSON
```

### Indent Aware Heredoc/Nowdoc {#indent-aware-docs}

[RFC](https://wiki.php.net/rfc/flexible_heredoc_nowdoc_indentation)

Sometimes you need a multiline string, for example, writing database migrations in SQL or when writing code that
generates code, so you bring in a Heredoc or Nowdoc variable. The downside of this is that if within
this structure, any indentation is significant for the resulting variable value. So if you need to ensure that the string
has no indentation, then you must drop it in your code. This can make your code difficult to read for you and other
developers on your team.

A class that generates a new PHP class, might look something like the below. Because the block of text should not be
indented in the resulting file, we have to shift the entire text completely to the left, ruining the clean indentation
of the `ClassGenerator` class.

```php
<?php

class ClassGenerator
{
    public static function generate(string $name): string
    {
        return <<<NEWCLASS
<?php

class $name
{
    public function __construct()
    {
    }
}
NEWCLASS;
    }
}
```

With the new syntax, the indentation of the closing marker defines the amount of indentation that should be **removed**
from the resulting string. Allowing our new class to look like this.

```php
<?php

class ClassGenerator
{
    public static function generate(string $name): string
    {
        return <<<NEWCLASS
            <?php

            class $name
            {
                public function __construct()
                {
                }
            }
            NEWCLASS;
    }
}
```

### Trailing Commas in Function/Method Calls {#trailing-commas-in-function-calls}

[RFC](https://wiki.php.net/rfc/trailing-comma-function-calls)

This one is pretty straightforward: function and method calls can now include a trailing comma. This has been allowed in
arrays, and I use it heavily. I appreciate having this option in multiline arrays, because it removes the possibility that
I might attempt to add a new line to the array and forgetting to add the comma. The same is true for why I am excited to
have this feature in functions, especially variadic functions.

```php
<?php

sprintf(
    'A very long string that %s wants to be able to add %s to',
    $bob,
    'strings'
)
```

In the above example, if I attempt to just add a new line belong `'strings'`, I also need to remember to add the comma
after `'strings'`. With PHP three, a trailing comma can be preemptively added and always maintained.

```php
<?php

sprintf(
    'A very long string that %s wants to be able to add %s to',
    $bob,
    'strings',
)
```