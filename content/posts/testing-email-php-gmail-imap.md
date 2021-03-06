---
title: "Testing Emails with PHP, Gmail, and IMAP"
date: 2019-01-16
tags: ["php", "testing", "email"]
---

I already discussed my open source project, [Courier](https://quartzy.github.io/courier), and writing [integration tests
for SMTP emails using MailHog](/test-email-php-mailhog). Most of the courier implementations do not use SMTP, though.
Even more importantly, I found that when testing email delivery through services like SparkPost and SendGrid there are
a lot of edge cases that should be known and understood. For example, there is an [issue sending emails to CC recipients
using templates with SparkPost](https://quartzy.github.io/courier/couriers/sparkpost/#temporary-fix-for-correctly-displaying-cc-header).
The only way to consistently ensure these errors are handled as the package is maintained is through integration tests.

A full implementation of these integration tests can be seen [here](https://github.com/quartzy/courier-sparkpost/blob/0.2.0/tests/SparkPostCourierIntegrationTest.php)

## The Idea

With this, I decided to write integration tests for each courier. The basic structure of a test I wanted was

1. Build a client
2. Create an email
3. Send the email to a known inbox
4. Wait for the email to arrive in the inbox
5. Parse the found email
6. Ensure the expected values exist on the delivered email

Then I would repeat the process with a templated email (where the markup for the email lived on the service's servers).

## The Implementation

I decided I would send the emails to an existing Gmail account, and pull the emails out of that inbox using the
functions from the PHP IMAP extension.

My first goal was to use a single Gmail account, just to make maintenence of the account easier. However, I still needed
to be able to receive emails into this account as the to, CC, and BCC recipients. For this, I set up three inboxes on
my Gmail account `Courier/To`, `Courier/CC`, and `Courier/BCC`. From here, I used a pattern of adding `+to`, `+cc`, and
`+bcc` to the recipient addresses in the email, such as `myaccount+to@gmail.com`, and I created filters to move emails
with the each suffix into it's respective inbox in Gmail.

Next, I needed to access the inboxes using PHP's IMAP functions. For this, I created an
[application password](https://support.google.com/accounts/answer/185833?hl=en) on my Gmail account. Once I did this,
I accessed the emails using code similar to the below.

**Security note: For this to work consistently on a CI server, your Gmail account should not have 2FA on it**

```php
<?php

$attempts = 5;
while ($attempts > 0) {
    /*
     * IMAP_SERVER = {imap.gmail.com:993/imap/ssl/novalidate-cert}
     * IMAP_USERNAME = myaccount@gmail.com
     * IMAP_PASSWORD = the app password created for my Gmail account
     */
    $conn = imap_open(getenv('IMAP_SERVER') . 'Courier/To', getenv('IMAP_USERNAME'), getenv('IMAP_PASSWORD'));

    $messages = imap_search($conn, 'SUBJECT "' . $subject . '"');

    if ($messages !== false) {
        // A message was found, so we can grab the body of it
        return imap_fetchbody($conn, $messages[0], '');
    }

    $attempts--;
    imap_close($conn);
    sleep(2);
}

return null;
```

Finally, I needed a consistent way to parse the MIME email content found by the IMAP function. I found a great package
called (Mail MIME Parser)[https://mail-mime-parser.org/]. And so instead of just returning the body of the email as a
string, my testing function would initialize a parser and return a `Message` object.

```php

<?php

use ZBateson\MailMimeParser\MailMimeParser;

...

    if ($messages !== false) {
        // A message was found, so we can grab the body of it
        $parser = new MailMimeParser();
        return $parser->parse(imap_fetchbody($conn, $messages[0], ''));
    }
```

## The Final Product

Putting all of this together, I was able to create a base test class for my integration testing that allowed me to easily
pull emails from the respective inbox based on the subject of the email as a parsed `Message` object
for comparison to expected values.

```php
<?php

declare(strict_types=1);

namespace Courier\Sparkpost\Test;

use ZBateson\MailMimeParser\Header\Part\ParameterPart;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message;

class IntegrationTestCase extends \PHPUnit\Framework\TestCase
{
    protected function getEmailDeliveredToTo(string $subject): ?Message
    {
        return $this->getEmailFromMailBox('Courier/To', $subject);
    }

    protected function getEmailDeliveredToCc(string $subject): ?Message
    {
        return $this->getEmailFromMailBox('Courier/CC', $subject);
    }

    protected function getEmailDeliveredToBcc(string $subject): ?Message
    {
        return $this->getEmailFromMailBox('Courier/BCC', $subject);
    }

    private function getEmailFromMailBox(string $mailBox, string $subject): ?Message
    {
        $parser = new MailMimeParser();

        $attempts = 5;
        while ($attempts > 0) {
            $conn = imap_open(getenv('IMAP_SERVER') . $mailBox, getenv('IMAP_USERNAME'), getenv('IMAP_PASSWORD'));

            $messages = imap_search($conn, 'SUBJECT "' . $subject . '"');

            if ($messages !== false) {
                return $parser->parse(imap_fetchbody($conn, $messages[0], ''));
            }

            $attempts--;
            imap_close($conn);
            sleep(2);
        }

        return null;
    }
}
```
