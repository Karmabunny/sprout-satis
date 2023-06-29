<?php

/**
 * This file contains some core configuration directives, including the
 * list of modules to load.  Another config file to look at
 * is _bootstrap_config.php which provides the value for IN_PRODUCTION
 */


/**
 * Base path of the web site.
 * Most common value would be '/' but it may be something else instead.
 * Should always have a trailing slash.
 */
$config['site_domain'] = '/';


/**
 * When PHP is called in CLI mode (e.g. a cron script), it cannot
 * autodetect the host name, so you need to specify it here.
 * If you don't, you will get an exception.
 *
 * Examples:
 *   example.com
 *   test.example.com
 */
if (IN_PRODUCTION) {
    $config['cli_domain'] = 'pkg.bunnysites.com';
} else {
    $config['cli_domain'] = 'pkg.bunnysites.com';
}


/**
 * Enabled sprout v3 modules
 */
Sprout\Helpers\Register::modules([
    'HomePage',
    'Satis',
]);
