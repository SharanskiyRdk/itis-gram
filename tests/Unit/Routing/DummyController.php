<?php

namespace Tests\Unit\Routing;

use App\Controllers\AbstractController;

class DummyController extends AbstractController
{
    public function show(): void
    {
        echo 'dummy-ok';
    }
}
