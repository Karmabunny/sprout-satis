Sprout 4 Satis Module
==================================

This is a thin wrapper around Satis to provide a Composer-compatible package server.

Additional features:
 - Authentication per site
 - Webhook build triggers


Requirements
------------

* PHP 8.2 or later

* A web server, e.g. Apache or nginx

* MySQL 8.0 or later, or MariaDB 10.4 or later

* Composer 2 or later


Installation
------------

Because this package is itself a private package, it must be installed directly from Github. Similarly for any other private dependencies used by the host site.

Create an access token here: https://github.com/settings/tokens/new

This can be either a fine-grained or classic token. These both have an expiry date (max 1 year) so this will need to be updated regularly to maintain access to new versions.

Install the token on the production host like so:

```sh
composer config github-oauth.github.com {github_pat_TOKEN}
```


Configuration
-------------

1. Add this package as a dependency:

```sh
composer require karmabunny/sprout-satis
```

2. Then install the module in the `config.php`:

```php
Register::modules(\SproutModules\Karmabunny\Satis\SatisModule::class);
```

3. Create a `satis.php` config and configure like so:

```php
$config['name'] = 'AUTHOR/NAME';
$config['github_token'] = getenv('GITHUB_TOKEN');
$config['whitelist_ips'] = [];
```

The `GITHUB_TOKEN` is another access token to provides access to the private packages in the repository. This can be the same as the one generated for installing the satis module itself, although your requirements may be different.

It's advised to keep this token non-versioned in the production environment file so that it can be kept safe and rotated independently of deployments.
