<?php

use GingTeam\RedBean\Facade as R;
use PHPUnit\Framework\TestCase;

beforeAll(function () {
    R::setup('sqlite:'.__DIR__.'/data.db');

    $user = R::dispense('users');
    $user->name = 'tnit';
    $user->signature = '12345';
    $user->callback_url = 'google.com';

    R::store($user);
});

it('throws exception', function () {
    getUser('1234');
})->throws(InvalidArgumentException::class, 'Invalid signature');

test('user test', function () {
    /** @var TestCase $this */
    $user = getUser('12345');
    $this->assertSame('tnit', $user->name);
});

test('validator test', function () {
    /** @var TestCase $this */
    $data = [
        'app_key' => '####',
        'amount' => 1000,
        'receive_id' => 1,
        'signature' => hash_hmac('md5', 'test', 'test'),
    ];

    $this->assertTrue(validate($data));
});
