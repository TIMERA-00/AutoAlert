<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The front-end build is not part of a test run: without this, every
        // test rendering a Blade view fails on a checkout without public/build.
        $this->withoutVite();
    }
}
