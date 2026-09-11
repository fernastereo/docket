<?php

declare(strict_types=1);

test('the central domain returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
