<?php

return [
    'baseUrl' => '',
    'production' => false,
    'siteName' => 'Chris Muthig',
    'siteDescription' => 'Musings on the path of my software development career',
    'siteAuthor' => 'Chris Muthig',

    // collections
    'collections' => [
        'posts' => [
            'author' => 'Chris Muthig', // Default author, if not provided in a post
            'sort' => '-date',
            'path' => 'posts/{filename}',
            'filter' => function ($post) {
                return !$post->draft;
            }
        ],
    ],

    // default categories
    'defaultCategories' => [
        'path' => 'categories/{filename}'
    ],

    // helpers
    'getDate' => function ($page) {
        return Datetime::createFromFormat('U', $page->date);
    },
    'getExcerpt' => function ($page, $length = 255) {
        $content = $page->excerpt ?? $page->getContent();
        $cleaned = strip_tags(
            preg_replace(['/<pre>[\w\W]*?<\/pre>/', '/<h\d>[\w\W]*?<\/h\d>/'], '', $content),
            '<code>'
        );

        $truncated = substr($cleaned, 0, $length);

        if (substr_count($truncated, '<code>') > substr_count($truncated, '</code>')) {
            $truncated .= '</code>';
        }

        return strlen($cleaned) > $length
            ? preg_replace('/\s+?(\S+)?$/', '', $truncated) . '...'
            : $cleaned;
    },
    'isActive' => function ($page, $path) {
        return ends_with(trimPath($page->getPath()), trimPath($path));
    },
];
