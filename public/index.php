<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Url;
use App\UrlRepository;
use App\Validator;
use DI\Container;
use DiDom\Document;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\PhpRenderer;


session_start();
$container = new Container();
$container->set('renderer', function () {
    return new PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new Messages();
});

$container->set(\PDO::class, function () {

    if (file_exists('../.env')) {
        $lines = file('../.env');
        foreach ($lines as $line) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
        }
    }

    $databaseUrl = parse_url($_ENV['DATABASE_URL']);
    $username = $databaseUrl['user'];
    $password = $databaseUrl['pass'];
    $host = $databaseUrl['host'];
    $dbName = ltrim($databaseUrl['path'], '/');
    $dsn = sprintf("pgsql:dbname=%s;host=%s", $dbName, $host);
    $conn = new \PDO($dsn, $username, $password);
    $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

    return $conn;
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {


    return $this->get('renderer')->render($response, 'index.phtml');
})->setName('main');


$app->post('/urls', function ($request, $response) {
    $url = $request->getParsedBodyParam('url');
    $validUrl = new Validator();
    $errors = $validUrl->validate($url);
    if (!isset($errors)) {
        $urlData = new Url();
        $newUrl = $urlData->fromArray([$url['name']]);
        $urlRepository = $this->get(UrlRepository::class);
        $checkResult = $urlRepository->check($url['name']);
        if (!$checkResult) {
            $urlRepository->save($newUrl);
            $id = $newUrl->getId();
            $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
            return $response->withRedirect("/urls/$id", 302);
        } else {
            $id = $checkResult['id'];
            $this->get('flash')->addMessage('success', 'Страница уже существует');
            return $response->withRedirect("/urls/$id", 302);
        }
    }
    $params = [
        'errors' => $errors
    ];
    $response->withStatus(204);
    return $this->get('renderer')->render($response, 'index.phtml', $params);
})->setName('post');


$app->get('/urls/{id}', function ($request, $response, array $args) {
    $flash = $this->get('flash')->getMessages();
    $id = $args['id'];
    $status = 'before check';
    $urlRepository = $this->get(UrlRepository::class);
    $urlDataArray = $urlRepository->find($id);
    $url = Url::makeObjectUrl($urlDataArray);
    $urlCheckRepo = $this->get(\App\UrlCheckRepository::class);
    $checkResultArray = $urlCheckRepo->findUrlCheck($id);
    $arrayUrlCheckObject = [];
    if ($checkResultArray) {
        $status = 'after check';
        $urlCheckObject = new \App\UrlCheck();
        foreach ($checkResultArray as $item) {
            $arrayUrlCheckObject[] = $urlCheckObject->makeUrlCheckObject($item);
        }
    }
    $params = [
        'flash' => $flash,
        'url' => $url,
        'status' => $status,
        'arrayUrlCheckObject' => $arrayUrlCheckObject,
    ];
    return $this->get('renderer')->render($response, 'url.phtml', $params);
})->setName('getUrlId');

$app->post('/urls/{url_id}/checks', function ($request, $response, array $args) use ($router) {
    $id = $args['url_id'];
    $address = $request->getParsedBodyParam('name');
    $client = new Client();
    try {
        $urlInform = $client->request('GET', $address, ['allow_redirects' => true, 'http_errors' => true]);
        //$urlInform = $client->request ( 'GET' ,  $address );
        //$urlInform = $client->get($address);
        $statusCod = $urlInform->getStatusCode();
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
        $body = $urlInform->getBody()->getContents();
        $document = new Document($body);
        $description = $document->first("meta[name=description]");
        if ($description) {
            $description = $description->attr('content');
        } else {
            $description = '';
        }
        $title = $document->first('title');
        if ($title) {
            $title = $title->text();
        } else {
            $title = '';
        }
        $h1 = $document->first('h1');
        if ($h1) {
            $h1 = $h1->text();
        } else {
            $h1 = '';
        }
        $urlChek = new \App\UrlCheck();
        $urlChekData = ['description' => $description,
                       'h1' => $h1,
                       'title' => $title,
                       'status_code' => $statusCod,
                       'url_id' => $id];
        $urlChekObject = $urlChek->makeUrlCheckObject($urlChekData);
        $repo = $this->get(\App\UrlCheckRepository::class);
        $repo->save($urlChekObject);
    } catch (ClientException $e) {
        $this->get('flash')->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
        return $response->withRedirect("/urls/$id");
    } catch (ServerException $e) {
        $this->get('flash')->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
        return $response->withRedirect("/urls/$id");
    } catch (TooManyRedirectsException $e) {
        $this->get('flash')->addMessage('warning', 'Проверка была выполнена успешно, но сервер ответил с ошибкой');
        $statusCod = $e->getCode();
        $h1 = "{$statusCod} Temporary Redirect";
        $title = "{$statusCod} Temporary Redirect";
        $description = '';
        $urlChekData = ['description' => $description,
                        'h1' => $h1,
                        'title' => $title,
                        'status_code' => $statusCod,
                        'url_id' => $id];
        $urlChekObject = (new \App\UrlCheck())->makeUrlCheckObject($urlChekData);
        $repo = $this->get(\App\UrlCheckRepository::class);
        $repo->save($urlChekObject);
    } catch (ConnectException $e) {
        $this->get('flash')->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
        return $response->withRedirect("/urls/$id");
    } catch (RequestException $e) {
        $this->get('flash')->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
        return $response->withRedirect("/urls/$id");
    } catch (\Throwable $e) {
        $this->get('flash')->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
        return $response->withRedirect("/urls/$id");
    }
    return $response->withRedirect("/urls/$id");
});

$app->get('/urls', function ($request, $response) {
    $repo = $this->get(\App\UrlCheckRepository::class);
    $allUrlChecks = $repo->findAllUrls();
    $params = [
        'allUrlChecks' => $allUrlChecks
    ];
    return $this->get('renderer')->render($response, 'urls.phtml', $params);
})->setName('urls');

$app->run();
