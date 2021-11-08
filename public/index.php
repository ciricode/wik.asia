<?php

use GingTeam\RedBean\Facade as R;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;

require __DIR__.'/../vendor/autoload.php';

R::setup('sqlite:'.__DIR__.'/../data.db');

R::freeze(false); // true in 'prod'

$app = AppFactory::create();

$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

$customErrorHandler = function (
    ServerRequestInterface $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails,
    ?LoggerInterface $logger = null
) use ($app): Response {
    $payload = ['error' => $exception->getMessage()];
    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(
        json_encode($payload, JSON_UNESCAPED_UNICODE)
    );

    return $response->withStatus(500);
};

$errorMiddleware = $app->addErrorMiddleware(false, false, false);
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);

$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write('Hello world');

    return $response;
});

$app->post('/api', function (Request $request, Response $response) {
    $data = $request->getParsedBody();

    if (validator($data)) {
        $user = getUser($data['signature']);
        postToCallback($user->callback_url, $data);
    }

    return $response;
});

$app->run();
