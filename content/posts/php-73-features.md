---
title: "PHP 7.3 Features I'm Excited About"
date: 2018-12-19
tags: ["php"]
---

By the time this post is live, PHP 7.3 should already have dropped. I am very happy with the quality of work released
by the core team since 7.0. They have built a number of amazing features into the language that focus on
developer productivity, which makes a difference when maintaining a large project. PHP 7.3 follows suit, including
several great features, but there are a few that I am especially excited about.

- [Throw on JSON Errors](#throw-on-json-error)
- [Indent Aware Heredoc/Nowdoc](#indent-aware-docs)
- [Trailing Commas in Function/Method Calls](#trailing-commas-in-function-calls)

### Throw on JSON Errors {#throw-on-json-error}

#### [RFC](https://wiki.php.net/rfc/json_throw_on_error)

Parsing JSON is a common requirement for most APIs. Until 7.3, the common pattern was to attempt to decode the
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

This was not an awful experience, but it does not match the `try/catch` pattern that most developers expect in PHP,
making it difficult to remember to do this check. We are now presented a new option in the `JSON_THROW_ON_ERROR` option.
This allows developers to tell PHP to throw an exception if it is unable to decode the JSON payload.

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

#### [RFC](https://wiki.php.net/rfc/flexible_heredoc_nowdoc_indentation)

A common use case I have found for writing heredoc/nowdoc styled strings is when writing
code that generates more code. The downside of this pattern is that within the heredoc/nowdoc, I have to
drop the natural indentation of the outer code to ensure the indentation of whatever is generated is correct, making the
whole file more difficult to read.

Such a piece of code, might look something like the below. Because the block of text should not be
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

With the new syntax, the indentation of the closing marker defines the amount of indentation that should be removed
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

#### [RFC](https://wiki.php.net/rfc/trailing-comma-function-calls)

This one is pretty straightforward: function and method calls can now include a trailing comma. This has been allowed in
arrays, and I use it in my projects as a standard convention. I appreciate having this option in multiline arrays because
it removes the possibility that I might attempt to add a new line to the array and forget to add the comma. The same 
is true for functions, especially variadic functions.

```php
<?php

sprintf(
    'A very long string that %s wants to be able to add %s to',
    $bob,
    'strings'
)
```

In the above example, if I attempt to just add a new line below `'strings'`, I also need to remember to add the comma
after `'strings'`. With PHP 7.3, a trailing comma can be preemptively added and always maintained.

```php
<?php

sprintf(
    'A very long string that %s wants to be able to add %s to',
    $bob,
    'strings',
)
```

Another time I have found a trailing comma useful is when generating code. Allowing the trailing comma means the
developer has to be just a little less careful handling the edge case of the last value in the list. For this reason,
I'm hopeful the core team can eventually support trailing commas on the function/method declaration as well, but that
was not included in this particular RFC.