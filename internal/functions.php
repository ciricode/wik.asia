<?php

use GingTeam\RedBean\Facade as R;
use RedBeanPHP\OODBBean;
use RedBeanPHP\RedException;

/**
 * @return OODBBean<mixed>
 *
 * @throws RedException
 * @throws InvalidArgumentException
 */
function getUser(string $signature): OODBBean
{
    $user = R::findOne('users', 'signature =?', [$signature]);

    if (null === $user) {
        throw new InvalidArgumentException('Invalid signature');
    }

    return $user;
}

/**
 * @param array<string|int> $params
 */
function postToCallback(string $url, array $params): void
{
    $options = [
        'http' => [
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'method' => 'POST',
            'content' => http_build_query($params),
        ],
    ];

    @file_get_contents($url, false, stream_context_create($options));
}

/**
 * @param array<string|int> &$data
 *
 * @throws InvalidArgumentException
 */
function validator(array &$data): bool
{
    $v = new Valitron\Validator($data);
    $v->rule('required', 'app_key');
    $v->rule('length', 'signature', 32);
    $v->rule('integer', 'amount');
    $v->rule('integer', 'receive_id');

    if ($v->validate()) {
        unset($data['app_key'], $data['signature']);

        return true;
    }

    return false;
}
