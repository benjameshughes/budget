<?php

declare(strict_types=1);
test('homepage redirects to dashboard', function () {
    $response = $this->get('/');

    $response->assertRedirect('/dashboard');
});
