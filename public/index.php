<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Flash\Messages;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use App\Validator;



$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);

$app->get('/urls', function ($request, $response)  {
    //$flash = $this->get('flash')->getMessages();
    $params = [
        'sites' => [],

    ];
    return $this->get('renderer')->render($response, 'urls.phtml', $params);
})->setName('urls');

$app->run();