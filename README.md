# Fuko\\Masked [![Latest Version](http://img.shields.io/packagist/v/fuko-php/masked.svg)](https://packagist.org/packages/fuko-php/masked) [![GitHub license](https://img.shields.io/github/license/fuko-php/masked.svg)](https://github.com/fuko-php/masked/blob/master/LICENSE) [![Codacy Badge](https://api.codacy.com/project/badge/Grade/df4745ccdac246c490dfd243368bd02e)](https://www.codacy.com/app/kktsvetkov/fuko-php.masked?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=fuko-php/masked&amp;utm_campaign=Badge_Grade)

**Fuko\\Masked** is a small PHP library for masking sensitive data: it replace blacklisted elements with their redacted values.

It is meant to be very easy to use. If you have any experience with trying to sanitise data for logging, or cleaning information that is publicly accessible, you know how annoying it is to have passwords or security token popup at various places of your dumps. ***Fuko\\Masked*** is meant to help with that.

## Basic use
In order to use it, you just need to feed your sensitive data (passwords, tokens, credentials) to `Fuko\Masked\Protect`

```php
use Fuko\Masked\Protect;

Protect::hideValue($secret_key); // hide the value inside the $secret_key var
Protect::hideInput('password', INPUT_POST); // hide the value of $_POST['password']

$redacted = Protect::protect($_POST);
```

...and that's it. The blacklisted values and inputs will be masked. The output of the above code is going to be

```php
// consider these values for the vars used
// $secret_key = '12345678';
// $_POST = array('username' => 'Bob', 'password' => 'WaldoPepper!', 'messages' => 'The secret key is 12345678');

$redacted = Protect::protect($_POST);
print_r($redacted);
```
```
Array
(
    [username] => Bob
    [password] => ████████████
    [messages] => The secret key is ████████
)
```

## How it works ?

***Fuko\\Masked*** does two things:

* first, there is the `\Fuko\Masked\Redact` class which is used to mask sensitive data
* second, `\Fuko\Masked\Protect` class is used to collect your sensitive data, and redact it

By doing the above, you are going to have redacted content with all the sensitive details blacklisted. You do not need to go looking inside all the dumps you create for passwords or credentials, instead you just register them with `\Fuko\Masked\Protect` and that class will mask them wherever it finds them: strings, arrays, big text dumps. It's that simple. The idea is not to have clumsy and overdressed library, but a simple tool that its job well.

## Examples

You know where your passwords and credentials are, and you want to blacklist them in any dumps you create. Here's how you would do it:
```php
use \Fuko\Masked\Protect;

// consider these values inside $config
// $config = array(
// 	'project_title' => 'My New Project!',
// 	'mysql_username' => 'me',
// 	'mysql_password' => 'Mlyk!',
// 	'mysql_database' => 'project',
// 	'root' => '/var/www/niakade/na/majnata/si',
// 	'i.am.stupid' => 'Mlyk! e egati parolata za moya project',
// 	);

Protect::hideValue($config['mysql_username']);
Protect::hideValue($config['mysql_password']);
Protect::hideValue($config['mysql_database']);

print_r(Protect::protect($config));
/* ... and the output is
Array
(
    [project_title] => My New Project!
    [mysql_username] => ██
    [mysql_password] => █████
    [mysql_database] => ███████
    [root] => /var/www/niakade/na/majnata/si
    [i.am.stupid] => █████ e egati parolata za moya ███████
)
*/
```

At some occasions you know that user-submitted data or other super-global inputs might contain sensitive data. In these cases you do not need to hide the actual value, but you can address the input array instead. In this example we are going to mask the "password" POST value:
```php
use \Fuko\Masked\Protect;

Protect::hideInput('password', INPUT_POST);

// later you need to do a dump of $_POST and ...
$_POST_redacted = Protect::protect($_POST);
/* ... and the output is
Array
(
    [email] => Bob@sundance.kid
    [password] => ███████
)
*/
```

Recently in Laravel 5 there is a [new feature](https://github.com/laravel/framework/pull/21336) added that introduces configuration option ["debug_blacklist"](https://laravel.com/docs/5.7/configuration#hiding-environment-variables-from-debug) for [Whoops](https://github.com/filp/whoops) to hide several sensitive variables. Here's how this can be done with `\Fuko\Masked\Protect` instead:

```php
use \Fuko\Masked\Protect;

Protect::hideInputs(array(
	INPUT_ENV => array(
		'APP_KEY',
		'DB_PASSWORD',
		'REDIS_PASSWORD',
		'MAIL_PASSWORD',
		'PUSHER_APP_KEY',
		'PUSHER_APP_SECRET',
		),
	INPUT_SERVER => array(
		'PHP_AUTH_PW',
		'APP_KEY',
		'DB_PASSWORD',
		'REDIS_PASSWORD',
		'MAIL_PASSWORD',
		'PUSHER_APP_KEY',
		'PUSHER_APP_SECRET',
		),
	INPUT_POST => array(
		'password',
		)
	)
);
```
After this setup, whatever information you pass through `\Fuko\Masked\Protect::protect()` it will mask the blacklisted inputs.

## Different Masking

You can use `\Fuko\Masked\Redact` in your project as the library for masking data. By default the class uses `\Fuko\Masked\Redact::disguise()` method for masking, with default settings that masks everything and that uses `█` as masking symbol. Here's how you can change its behaviour:
```php
use \Fuko\Masked\Redact;

/* leave 4 chars unmasked at the end, and use '*' as masking symbol */
Redact::setRedactCallback( [Redact::class, 'disguise'], [4, '*']);
echo Redact::redact('1234567890'); // Output is '******7890'

/* leave 4 chars unmasked at the beginning, and use '🤐' as masking symbol */
Redact::setRedactCallback( [Redact::class, 'disguise'], [-4, '🤐']);
echo Redact::redact('1234567890'); // Output is '1234🤐🤐🤐🤐🤐🤐'
```

You can set your own callback for masking with `\Fuko\Masked\Redact` class:
```php
use \Fuko\Masked\Redact;

Redact::setRedactCallback( function($var) { return '💩'; } );
echo Redact::redact('1234567890'); // Output is '💩'
```
