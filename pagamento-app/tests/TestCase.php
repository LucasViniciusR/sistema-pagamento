<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.mongodb' => config('database.connections.mongodb_testing')]);
    }

    protected function tearDown(): void
    {
        \DB::connection('mongodb')->getMongoClient()->dropDatabase(config('database.connections.mongodb.database'));
        parent::tearDown();
    }
}
