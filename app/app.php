<?php

use Symfony\Component\HttpFoundation\Request;

use Silex\Provider\FormServiceProvider;

use Symfony\Component\Config\FileLocator;
use Herrera\Wise\Loader\YamlFileLoader as WYamlFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;

use PloufPlouf\PloufPloufServiceProvider;

/**
 * PloufPlouf
 *
 * @todo add logs
 * @todo add tests
 * @todo abstract redis implementation to allow others data store (sqlite etc)
 */
$app = new Silex\Application();

// ---------------------------------------- SERVICES ----------------------------------------

// Config
$locator = new FileLocator(__DIR__.'/config/');
$loader = new WYamlFileLoader($locator);

$app->register(
    new Herrera\Wise\WiseServiceProvider(),
    array(
        'wise.cache_dir' => __DIR__ . '/../cache',
        // 'wise.path' => __DIR__.'/config/',
        'wise.loader' => $loader,
        'wise.options' => array(
            'parameters' => $app
        )
    )
);

$app['config'] = $app['wise']->load('global.yml');
$app['config'] = array_merge($app['config'], $app['wise']->load(APPLICATION_ENV . '.yml'));
$app['debug'] = $app['config']['debug'];

// Global
$app['app_path'] = __DIR__;

// Session
$app->register(new Silex\Provider\SessionServiceProvider());

// Url generator
// @note needs to be registered before Twig
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

// Form
$app->register(new FormServiceProvider());

// Validation
$app->register(new Silex\Provider\ValidatorServiceProvider());

// Translation
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale_fallbacks' => array('en'),
    'translator.messages' => array()
));

$app['translator'] = $app->share($app->extend('translator', function($translator, $app) {
    $translator->addLoader('yaml', new YamlFileLoader());

    $translator->addResource('yaml', __DIR__.'/locales/site.en.yml', 'en');
    $translator->addResource('yaml', __DIR__.'/locales/site.fr.yml', 'fr');

    $translator->addResource('yaml', __DIR__.'/locales/success_email.en.yml', 'en', 'success_email');
    $translator->addResource('yaml', __DIR__.'/locales/success_email.fr.yml', 'fr', 'success_email');

    return $translator;
}));

// SwiftMailer
$app->register(new Silex\Provider\SwiftmailerServiceProvider());
$app['swiftmailer.options'] = $app['config']['swiftmailer.options'];

// Twig
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
    'twig.options' => array('strict_variables' => false)
));

// Twig Asset
$app['twig']->addExtension(new \Entea\Twig\Extension\AssetExtension($app));

// Twig Globals
$app['twig']->addGlobal('analytics', $app['config']['analytics']);
$app['twig']->addGlobal('env', APPLICATION_ENV);

// Redis (Predis)
$app->register(new Predis\Silex\PredisServiceProvider(), $app['config']['predis']);





// Business domain services
$app->register(new PloufPloufServiceProvider(), array());


// ---------------------------------------- MISC APP ----------------------------------------

$app->before(function (Request $request) use ($app) {

    // set locale of user, will be used if supported
    $app['locale'] = $request->getPreferredLanguage();

    // Get or Set UID in Session
    // Used to identify user w/ Redis
    $uid = $app['session']->get('uid');
    if (empty($uid)) {
        $uid = uniqid();
        $app['session']->set('uid', $uid);
    }
    $app['uid'] = $uid;
    unset($uid);

    // convert json data to post data
	if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
		$data = json_decode($request->getContent(), true);
		$request->request->replace(is_array($data) ? $data : array());
	}
});

// Error handling
$app->error(function (\Exception $e, $code) use($app) {

    $code = ($e->getCode() == 0) ? $code : $e->getCode();

	switch ($code) {
        case 401:
            $message = "";
            break;
		case 404:
			$message = 'The requested url could not be found.';
			break;
		case 400:
			$message = $e->getMessage();
			break;
		default:
			$message = $e->getMessage();

		// if ($app['debug'] !== true) { $message = 'We are sorry, but something went terribly wrong.'; }
	}

	$responseArray = [
		'message' => $message,
		'code' => $code
	];

	return $app->json($responseArray, $code, array('X-Status-Code' => $code));
});

return $app;