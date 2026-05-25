<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Database;
use App\Models\Model;
use PHPUnit\Framework\MockObject\MockObject;

trait DatabaseMockTrait
{
    protected function bindDatabaseMock(Database|MockObject $dbMock): void
    {
        $dbRef = new \ReflectionClass(Database::class);
        $dbProp = $dbRef->getProperty('instance');
        $dbProp->setAccessible(true);
        $dbProp->setValue(null, $dbMock);

        $modelRef = new \ReflectionClass(Model::class);
        $modelProp = $modelRef->getProperty('db');
        $modelProp->setAccessible(true);
        $modelProp->setValue(null, $dbMock);
    }

    protected function resetDatabaseMock(): void
    {
        $dbRef = new \ReflectionClass(Database::class);
        $dbProp = $dbRef->getProperty('instance');
        $dbProp->setAccessible(true);
        $dbProp->setValue(null, null);

        $modelRef = new \ReflectionClass(Model::class);
        $modelProp = $modelRef->getProperty('db');
        $modelProp->setAccessible(true);
        $modelProp->setValue(null, null);
    }
}
