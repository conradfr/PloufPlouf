<?php

namespace PloufPlouf;

use Silex\Application;
use Silex\ServiceProviderInterface;

class PloufPloufServiceProvider implements ServiceProviderInterface {

    public function register(Application $app)
    {
        $app['randomizer'] = function ($app) {
            return new Randomizer();
        };

        $app['emailer'] = $app->share(function ($app) {
            return new Mailer($app['mailer'], $app['twig'], $app['predis'], $app['config']['blacklist']['message_blacklist_ttl']);
        });
    }

    public function boot(Application $app)
    {
    }
}