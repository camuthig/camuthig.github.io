---
extends: _layouts.post
section: content
title:  "GraphQL Getters with Laravel Eloquent"
date:  2018-05-29T21:37:20-07:00
categories: ["laravel", "php", "graphql"]
---

I recently began work on a proof-of-concept project I have been mentally shaping for some time now. In building this, I
chose to stick with Laravel as a framework to build off of, as it is something I am familiar with, but I wanted to serve
my application's API using GraphQL instead of normal Laravel resources. For the task, I chose the 
[Laravel GraphQL](https://github.com/Folkloreatelier/laravel-graphql) package, as I am already familiar with the underlying
GraphQL implementation. My data model is very simple, with only a few columns of data and one or two relationships, so I
chose to stick with the most simple features of Eloquent in my implementation as well. Altogether, the GraphQL package
and Eloquent have made the creation of the barebones project simple.

I ran into one issue along the way, however: snakecase. I traditionally prefer using `snake_case` naming conventions
when defining columns in my database. However, with GraphQL, I have found `camelCase` to be much more natural. The
delimma arose from the fact that Eloquent maps attribute names directly as they are in the database, and Laravel dropped
support for converting to camelcase a [couple of years ago](https://github.com/laravel/ideas/issues/41). In my opinion,
this decision was fine, and I believe it is the job of the API's presentation layer to decide this stylistic
formatting, rather than enforcing it at the model layer. To that end, I implemented a simple default resolve function to
handle transforming the data:

```php
// config/graphql.js

// All other configuration values

    /*
     * Overrides the default field resolver
     *
     * The given implementation supports finding the snake_case variable names as defined by the Laravel
     * Eloquent models.
     */
    'defaultFieldResolver' => function ($root, $args, $context, $info) {
        $property = \GraphQL\Executor\Executor::defaultFieldResolver($root, $args, $context, $info);

        if ($property === null) {
            $fieldName = snake_case($info->fieldName);

            if ($root instanceof \Illuminate\Database\Eloquent\Model
                && in_array($fieldName, array_keys($root->getAttributes()))) {
                $property = $root->$fieldName;
            }
        }

        return $property;
    },

// Remainder of the configuration
```

A more robust solution might be to use a library such as [Symfony
PropertyAccess](https://symfony.com/doc/current/components/property_access.html) to support finding attributes in a
number of locations. This would have to be weighed against pulling in the additional dependency and  the extra logic, of
course.

It is important to also note that while this solution provides automatic mappings from `Eloquent name -> GraphQL name`,
it does not provide the inverse, and in my mutation and query resolvers, I am explicitly taking the input variables and
mapping them to the snakecase names of my models as necessary. 