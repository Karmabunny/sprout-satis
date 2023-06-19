SproutCMS 3.2 Application Template
==================================

SproutCMS is a flexible and feature rich cms and application framework, developed in PHP,
designed to enable quick and agile custom development. SproutCMS was built to reward
innovation and encourage developers to produce complex applications.
It is built by developers, for developers.


Requirements
------------

* PHP 7.4 or later

* A web server, e.g. Apache or nginx

* MySQL 5.7 or later, or MariaDB 10.3 or later

* Composer 2 or later


Getting started
---------------

1. Run `composer create-project sproutcms/site`
2. Run `composer serve`
3. Browse to http://localhost:8080/


### Docker

A sample docker configuration is also provided.

This a basic Nginx + MariaDB + PHP-FPM installation.

```sh
composer create-project sproutcms/site
composer install
./start.sh up
```

> Browse to http://localhost:8080/


Documentation
-------------

For more, visit [`sproutcms/cms`](https://github.com/Karmabunny/sprout3).

