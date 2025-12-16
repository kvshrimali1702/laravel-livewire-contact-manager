<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => config('app.key') ?: 'base64:'.base64_encode(Str::random(32)),
        ]);
    }
}
