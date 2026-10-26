<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * POST/PUT/PATCH/DELETE helpers bypass CSRF only (auth, throttle and
     * validation still run), mirroring a browser with a valid token.
     */
    protected function csrfExemptPost(string $uri, array $data = [], array $headers = [])
    {
        return $this->withoutMiddleware(ValidateCsrfToken::class)->post($uri, $data, $headers);
    }
}
