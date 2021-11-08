<?php

use Cake\Validation\Validator;
use GingTeam\RedBean\Facade as R;
use RedBeanPHP\OODBBean;
use RedBeanPHP\RedException;

/**
 * @return OODBBean<string,mixed>
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
    $errors = (new Validator())
        ->notEmptyString('app_key')
        ->lengthBetween('signature', [32, 32])
        ->decimal('amount')
        ->decimal('receive_id')
        ->validate($data)
    ;

    if ([] !== $errors) {
        return false;
    }

    unset($data['app_key'], $data['signature']);

    return true;
}
