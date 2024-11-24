<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Flash\Messages;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\PhpRenderer;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use App\Validator;
use App\UrlRepository;
use App\Url;
use GuzzleHttp\Client;


session_start();
$container = new Container();
$container->set('renderer', function () {
    return new PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new Messages();
});

$container->set(\PDO::class, function () {
    $dsn = 'pgsql:dbname=hexletProject3;host=127.0.0.1';
    $user = 'oleg';
    $password = '';
    $conn = new \PDO($dsn, $user, $password);
    $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    return $conn;
});

//$initFilePath = implode('/', [dirname(__DIR__), 'init.sql']);
//$initSql = file_get_contents($initFilePath);
//$container->get(\PDO::class)->exec($initSql);

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response)  {


    return $this->get('renderer')->render($response, 'index.phtml');
})->setName('main');

$app->get('/test', function ($request, $response)  {
    $client = new Client();
    $response = $client->get('https://impx.ru');
    var_dump($response->getStatusCode(), $response->getBody()->getContents());
    die();
    return $this->get('renderer')->render($response, 'index.phtml');
});


$app->post('/', function ($request, $response)  {
    $url = $request->getParsedBodyParam('url');
    $validUrl = new Validator();
    $errors = $validUrl->validate($url);
    if (!isset($errors)){
        $urlData = new Url();
        $newUrl = $urlData->fromArray([$url['name']]);
        $urlRepository = $this->get(UrlRepository::class);
        $checkResult = $urlRepository->check($url['name']);
        if(!$checkResult) {
            $urlRepository->save($newUrl);
            $id = $newUrl->getId();
            $this->get('flash')->addMessage('success', 'Усешно добавлено');
            return $response->withRedirect("/urls/$id");
        } else {
            $id = $checkResult['id'];
            $this->get('flash')->addMessage('success', 'Страница уже существует');
            return $response->withRedirect("/urls/$id");
        }
    }
    $params = [
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response, 'index.phtml', $params);
})->setName('post');


$app->get('/urls/{id}', function ($request, $response, array $args)  {
    $flash = $this->get('flash')->getMessages();
    $id = $args['id'];
    $urlRepository = $this->get(UrlRepository::class);
    $urlDataArray = $urlRepository->find($id);
    $urlData = new Url;
    $url = $urlData->makeOjectUrl($urlDataArray);
    $status = 'before check';

    $params = [
        'flash' => $flash,
        'id' => $id,
        'url' => $url,
        'status' => $status,
    ];
    return $this->get('renderer')->render($response, 'url.phtml', $params);
})->setName('getUrlId');

$app->post('/urls/{url_id}/checks', function ($request, $response, array $args) use ($router) {
    $id = $args['url_id'];
    $address = $request->getParsedBodyParam('name');
    $client = new Client();
    $urlInform = $client->get($address);
    $statusCod = $urlInform->getStatusCode();
    $body = $urlInform->getBody();
    file_put_contents('temprorary.txt', $body); die();
    $this->get('flash')->addMessage('success', 'Страница успешно проверена');


    //$status = 'after check';


    $route = $router->urlFor('getUrlId', ['id' => $id], [ 'status'=>$status, 'id'=>$id]);
    return $response
        ->withHeader('Location', $route)
        ->withStatus(302);
    //return $response->withRedirect($route);
    //return $response->withRedirect("/urls/$id",301, $params);
    //return $response->withRedirect($this->router->urlFor('getUrlsId', [], $params));
    //return $this->get('renderer')->render($response, 'url.phtml', $params);
});
$app->run();

