<?php

namespace Tests;

use App\Services\EntityHierarchyService;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        PermissionResolver::clearCache();

        // Static caches in EntityHierarchyService hold entity models across
        // tests, which become stale when RefreshDatabase resets the DB.
        EntityHierarchyService::clearCache();
    }
}
