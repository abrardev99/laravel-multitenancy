<?php

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\PrefixCacheTask;
use Spatie\Multitenancy\Tests\TestClasses\CacheService;

beforeEach(function () {
    config()->set('multitenancy.switch_tenant_tasks', [PrefixCacheTask::class]);

    config()->set('cache.default', 'redis');

    app()->forgetInstance('cache');

    app()->forgetInstance('cache.store');

    app('cache')->flush();
});

it('will separate the cache prefix for each tenant', function () {
    $originalPrefix = config('cache.prefix') . ':';

    expect($originalPrefix)
        ->toEqual(app('cache')->getPrefix())
        ->toEqual(app('cache.store')->getPrefix());

    /** @var \Spatie\Multitenancy\Models\Tenant $tenantOne */
    $tenantOne = Tenant::factory()->create();
    $tenantOne->makeCurrent();
    $tenantOnePrefix = 'tenant_id_' . $tenantOne->id . ':';

    expect($tenantOnePrefix)
        ->toEqual(app('cache')->getPrefix())
        ->toEqual(app('cache.store')->getPrefix());

    /** @var \Spatie\Multitenancy\Models\Tenant $tenantOne */
    $tenantTwo = Tenant::factory()->create();
    $tenantTwo->makeCurrent();
    $tenantTwoPrefix = 'tenant_id_' . $tenantTwo->id . ':';

    expect($tenantTwoPrefix)
        ->toEqual(app('cache')->getPrefix())
        ->toEqual(app('cache.store')->getPrefix());
});

it('will separate the cache for each tenant', function () {
    cache()->put('key', 'cache-landlord');

    /** @var \Spatie\Multitenancy\Models\Tenant $tenantOne */
    $tenantOne = Tenant::factory()->create();
    $tenantOne->makeCurrent();
    $tenantOneVal = 'tenant-' . $tenantOne->domain;

    expect(cache())->has('key')->toBeFalse();

    cache()->put('key', $tenantOneVal);

    /** @var \Spatie\Multitenancy\Models\Tenant $tenantTwo */
    $tenantTwo = Tenant::factory()->create();
    $tenantTwo->makeCurrent();
    $tenantTwoVal = 'tenant-' . $tenantTwo->domain;
    expect(cache())->has('key')->toBeFalse();
    cache()->put('key', $tenantTwoVal);

    $tenantOne->makeCurrent();
    expect($tenantOneVal)
        ->toEqual(app('cache')->get('key'))
        ->toEqual(app('cache.store')->get('key'));

    $tenantTwo->makeCurrent();
    expect($tenantTwoVal)
        ->toEqual(app('cache')->get('key'))
        ->toEqual(app('cache.store')->get('key'));

    Tenant::forgetCurrent();
    expect(cache())->get('key')->toEqual('cache-landlord');
});

test('prefix separate cache well enough using CacheManager dependency injection', function () {
    $this->app->singleton(CacheService::class);

    app()->make(CacheService::class)->handle();

    expect(cache('key'))->toBe('central-value');

    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $tenant1->makeCurrent();

    expect(cache('key'))->toBeNull();
    app()->make(CacheService::class)->handle();
    expect(cache('key'))->toBe($tenant1->getKey());

    $tenant2->makeCurrent();

    expect(cache('key'))->toBeNull();
    app()->make(CacheService::class)->handle();
    expect(cache('key'))->toBe($tenant2->getKey());

    Tenant::forgetCurrent();

    expect(cache('key'))->toBe('central-value');
});
