<?php

use GingTeam\RedBean\Facade as R;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;

require __DIR__.'/../vendor/autoload.php';

$app = AppFactory::create();

$app->add(function (Request $request, RequestHandlerInterface $requestHandler) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../');
    $dotenv->load();
    $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);
    $dotenv->required('PROD')->isBoolean();

    R::setup(
        sprintf('mysql:host=%s;dbname=%s', $_ENV['DB_HOST'], $_ENV['DB_NAME']),
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    R::freeze(getenv('PROD'));

    $response = $requestHandler->handle($request);

    return $response
        ->withStatus(200)
        ->withHeader('X-Powered-By', 'Wik.asia')
        ->withHeader('Content-Type', 'application/json');
});

$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

$errorHandler = function (
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
$errorMiddleware->setDefaultErrorHandler($errorHandler);

$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(
        json_encode(['messages' => 'Hello World'], JSON_UNESCAPED_UNICODE)
    );

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
