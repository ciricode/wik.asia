<?php

use Dotenv\Exception\InvalidEncodingException;
use Dotenv\Exception\InvalidPathException;
use GingTeam\RedBean\Facade as R;
use RedBeanPHP\OODBBean;
use RedBeanPHP\RedException;

/**
 * @throws InvalidPathException
 * @throws InvalidEncodingException
 * @throws RedException
 */
function init(): void
{
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
    $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);
    $dotenv->required('PROD')->isBoolean();
    $dotenv->required('MAX_RETRIES')->isInteger();

    R::setup(
        sprintf('mysql:host=%s;dbname=%s', $_ENV['DB_HOST'], $_ENV['DB_NAME']),
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    R::freeze(getenv('PROD'));
}

/**
 * @return OODBBean<mixed>
 *
 * @throws RedException
 * @throws InvalidArgumentException
 */
function getUser(string $signature): OODBBean
{
    $user = R::findOne('users', 'signature = ?', [$signature]);

    if (null === $user) {
        throw new InvalidArgumentException('Invalid signature');
    }

    return $user;
}

/**
 * @param array<string|int> $data
 *
 * @return int Returns the number of retries
 */
function postToCallback(string $url, array $data): int
{
    $client = new Curl\Curl();
    $client->setRetry($_ENV['MAX_RETRIES']);
    $client->post($url, $data);

    return $client->retries;
}

/**
 * @param array<string|int> &$data
 *
 * @throws InvalidArgumentException
 */
function validate(array &$data): bool
{
    $v = (new Valitron\Validator($data))
        ->rule('required', 'app_key')
        ->rule('length', 'signature', 32)
        ->rule('integer', 'amount')
        ->rule('integer', 'receive_id');

    if ($v->validate()) {
        unset($data['app_key'], $data['signature']);

        return true;
    }

    return false;
}
