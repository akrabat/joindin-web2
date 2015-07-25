<?php

// To help the built-in PHP dev server, check if the request was actually for
// something which should probably be served as a static file

if (in_array(substr($_SERVER['REQUEST_URI'], -4), ['.css', '.jpg', '.png'])) {
	return false;
}

// include dependencies
require '../vendor/autoload.php';

session_cache_limiter(false);
session_start();

// include view controller
require '../app/src/View/Filters.php';
require '../app/src/View/Functions.php';

$config = array();
$configFile = realpath(__DIR__ . '/../config/config.php');
if (is_readable($configFile)) {
    include $configFile;
} else {
    include realpath(__DIR__ . '/../config/config.php.dist');
}

// Wrap the Config Data with the Application Config object
$config['slim']['custom'] = new \Application\Config($config['slim']['custom']);

// initialize Slim
$container = new \Slim\Container($config['slim']);
$app = new \Slim\App($container);

if ($config['slim']['mode'] == 'development') {
    error_reporting(-1);
    ini_set('display_errors', 1);
    ini_set('html_errors', 1);
    ini_set('display_startup_errors', 1);

    $config['slim']['twig']['debug'] = true;
};

// setup Twig
$view = new \Slim\Views\Twig(
    __DIR__ . '/../app/templates',
    $config['slim']['twig']
);
$view->addExtension(new Twig_Extension_Debug());
$view->addExtension(new \Slim\Views\TwigExtension($app->router, $app->request->getUri()));
$container['view'] = $view;


// Pass the current mode to the template, so we can choose to show
// certain things only if the app is in live/development mode
$view->getEnvironment()->addGlobal('slim_mode', $config['slim']['mode']);

// Other variables needed by the main layout.html.twig template
$view->getEnvironment()->addGlobal('google_analytics_id', $config['slim']['custom']['googleAnalyticsId']);
$view->getEnvironment()->addGlobal('user', (isset($_SESSION['user']) ? $_SESSION['user'] : false));

// initialize Joindin filters and functions
View\Filters\initialize($view->getEnvironment(), $app);
View\Functions\initialize($view->getEnvironment(), $app);

// register error handlers
$app->error(function (\Exception $e) use ($app) {
    $app->render('Error/error.html.twig', ['exception' => $e]);
});

$app->notFound(function () use ($app) {
    $app->render('Error/404.html.twig');
});

// register middlewares
$app->add(new Middleware\ValidationMiddleware());
$app->add(new Middleware\FormMiddleware());

// register routes
new Application\ApplicationController($app);
new Event\EventController($app);
new Search\SearchController($app);
new User\UserController($app);
new Talk\TalkController($app);

// execute application
$app->run();
