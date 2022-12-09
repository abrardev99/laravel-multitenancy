<?php

namespace Spatie\Multitenancy\Tests\TestClasses;

use Illuminate\Cache\Repository;
use Spatie\Multitenancy\Models\Tenant;

class CacheService
{
    public function __construct(
        protected Repository $cache
    ){
    }

    public function handle(): void
    {
        if (Tenant::checkCurrent()) {
            $this->cache->put('key', Tenant::current()->getKey());
        } else {
            $this->cache->put('key', 'central-value');
        }
    }
}
