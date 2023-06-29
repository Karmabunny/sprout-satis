KB Private Packages
==================================

This is a thin wrapper around Satis to provide a Composer-compatible package server.

Additional features:
 - Authentication per site
 - Webhook build triggers


Requirements
------------

* PHP 7.4 or later

* A web server, e.g. Apache or nginx

* MySQL 5.7 or later, or MariaDB 10.3 or later

* Composer 2 or later


Installation
------------

This repo needs access to the private repositories that it is packaging. Create a github access token here:

https://github.com/settings/tokens/new

This can be either a fine-grained or classic token. Note that these have an
expiry date (max 1 year) so this will need to be updated regularly. Package pulls will continue to work, but publishing packages - either manually
or with webhooks will fail to complete.

Install this token the on production box in _two_ places:

1. For installing remote auth packages for the application itself:
   > `composer config github-oauth.github.com <github_pat_TOKEN>`

2. For pulling packages into the repository:
   - edit `.env`
   - > `GITHUB_TOKEN=<github_pat_TOKEN>`

