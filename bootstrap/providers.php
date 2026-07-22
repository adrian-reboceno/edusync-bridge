<?php

use App\Providers\AppServiceProvider;
use Auth\User\Infrastructure\Providers\AuthServiceProvider;
use Infrastructure\Providers\IntegrationServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    IntegrationServiceProvider::class,
];
