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
    $user = getUser('12345');

    /** @var TestCase $this */
    $this->assertSame('tnit', $user->name);
});

test('validator test', function () {
    $data = [
        'app_key' => '####',
        'amount' => 1000,
        'receive_id' => 1,
        'signature' => hash_hmac('md5', 'test', 'test'),
    ];

    /** @var TestCase $this */
    $this->assertTrue(validate($data));
});

test('retry test', function () {
    $_ENV['MAX_RETRIES'] = 2;

    $retries1 = postToCallback('https://httpbin.org/status/503', []);
    $retries2 = postToCallback('https://httpbin.org/post', []);

    /* @var TestCase $this */
    $this->assertEquals(2, $retries1);
    $this->assertEquals(0, $retries2);
});
