<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc\Schema;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\SchemaException;
use Exception;
use Jtl\Connector\Dbc\DbcRuntimeException;
use Jtl\Connector\Dbc\TableStub;
use Jtl\Connector\Dbc\TestCase;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use Throwable;

class TableRestrictionTest extends TestCase
{
    /**
     * @throws DBALException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws DbcRuntimeException
     * @throws SchemaException
     * @throws \RuntimeException
     */
    public function testInitializationSuccessful(): void
    {
        $tableSchema = $this->table->getTableSchema();
        $column      = TableStub::B;
        $value       = 'a string';
        $restriction = new TableRestriction($tableSchema, $column, $value);
        $this->assertEquals($tableSchema, $restriction->getTable());
        $this->assertEquals($column, $restriction->getColumnName());
        $this->assertEquals($value, $restriction->getColumnValue());
    }

    /**
     * @throws DBALException
     * @throws DbcRuntimeException
     * @throws SchemaException
     * @throws \RuntimeException
     */
    public function testInitializationWithNotExistingColumn(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionCode(SchemaException::COLUMN_DOESNT_EXIST);
        $tableSchema = $this->table->getTableSchema();
        new TableRestriction($tableSchema, 'yolo', 'c');
    }

    /**
     * @throws DBALException
     * @throws SchemaException
     * @throws DbcRuntimeException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws \RuntimeException
     */
    public function testCreate(): void
    {
        $tableSchema = $this->table->getTableSchema();
        $column      = TableStub::C;
        $value       = new \DateTimeImmutable('2007-08-31T16:47+00:00');
        $restriction = TableRestriction::create($tableSchema, $column, $value);
        $this->assertEquals($tableSchema, $restriction->getTable());
        $this->assertEquals($column, $restriction->getColumnName());
        $this->assertEquals($value, $restriction->getColumnValue());
    }

    /**
     * @throws DBALException
     * @throws Throwable
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->table = new TableStub($this->getDBManager());
        parent::setUp();
        $this->insertFixtures($this->table, self::getTableStubFixtures());
    }
}
