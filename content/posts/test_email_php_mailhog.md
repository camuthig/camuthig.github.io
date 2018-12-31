---
title: "Testing Emails with MailHog and PHP"
date: 2019-01-02
tags: ["php", "email", "testing"]
---

I have been working on an [open source project](https://quartzy.github.io/courier), on and off, for the last couple of years that is designed to give
developers a standard, concise interface for delivering emails in PHP through third-party SMTP providers, like SparkPost and
SendGrid. I recently decided to break out the logic for each of the providers into separate packages, making the core
project contain just the interfaces, exceptions, and some helpful traits. I also decided that I should include an
implementation of the interface to allow developers to deliver test emails using the built-in `mail` function, and I wanted
to make sure the logic was thoroughly tested in an end-to-end manner: what the developer sends on the interface should
make it all the way to the expected inboxes. 

The pull request implementing the delivery logic as well as the below documented testing can be found on [GitHub](https://github.com/quartzy/courier/pull/33).

The general structure of an end-to-end test, here, is that that the test should

1. Build an email
1. Deliver the email
1. Retrieve the delivered email
1. Compare the delivered email to the expectations

### MailHog and MailMimeParser

[MailHog](https://github.com/mailhog/MailHog) is an email testing tool that acts as a drop in replacement for a SMTP
server. The idea is to run MailHog and configure it as your SMTP server. MailHog accepts the
requests, holding onto the emails on your local machine, and it supplies an API to pull those emails back out of it
later. Once I had the email data, the goal was to parse it into an easy to use format. For this, I used the 
[MimeMailParser](https://github.com/zbateson/mail-mime-parser). This is an awesome package that takes the raw MIME
data of an email and transforms it into an object. Because I wanted to test-drive the implementation, the first step
was to write a function to allow me to get the emails out of MailHog and parse them into an object for comparison.

My original thought was to generate a MailHog client using their Swagger API definitions. However, I quickly found
that the [Swagger does not match the API](https://github.com/mailhog/MailHog/issues/233). I only needed
to make a single request to the server and grab one value, so I decided to keep the dependencies in the tests to zero
and just use curl to get what I needed.

```php
<?php

    private function getEmail(string $subject): ?Message
    {
        // It takes a second or two for the email to appear in MailHog, so this is just a simple solution that has worked for me
        sleep(2);

        $ch = curl_init('http://localhost:8025/api/v2/search?kind=containing&query=' . urlencode($subject));

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        if ($result === false) {
            throw new \Exception('Unable to access MailHog server');
        }

        $results = json_decode($result, true);

        if (!empty($results) && $results['count']) {
            $message = $results['items'][0];

            $parser = new MailMimeParser();

            $message = $parser->parse($message['Raw']['Data']);

            return $message;
        }

        return  null;
    }
```

### Running MailHog

I used Docker Compose to easily run MailHog on whatever computer I might be using for my testing. The upside of this
is that it also makes it easier to set everything up on CI servers as well. I used the default servers, but alternatives
could be used instead.

```yaml
version: "3"

services:
  mailhog:
    image: "mailhog/mailhog"
    ports:
      - "1025:1025"
      - "8025:8025"
```

MailHog can then be run using `docker-compose up -d`

### Continuous Testing

The project runs tests automatically using Travis CI, so I needed to ensure I could run these integration tests
there as well. This required a few changes to my existing Travis CI configuration file:

1. Ensure sudo is enabled
1. Start docker
1. Install MailHog and start the server
1. Configure PHP to use the `mhsendmail` function provided by MailHog instead of `sendmail`

```yaml
sudo: required

services:
  - docker

before_install:
  - wget https://github.com/mailhog/mhsendmail/releases/download/v0.2.0/mhsendmail_linux_amd64
  - chmod +x mhsendmail_linux_amd64
  - sudo mv mhsendmail_linux_amd64 /usr/local/bin/mhsendmail
  - docker-compose up -d

before_script:
  - echo 'sendmail_path = /usr/local/bin/mhsendmail' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini
```
