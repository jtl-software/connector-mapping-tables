<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace Jtl\Connector\Dbc\Schema;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\SchemaException;
use Jtl\Connector\Dbc\TestCase;
use Jtl\Connector\Dbc\TableStub;

class TableRestrictionTest extends TestCase
{
    protected function setUp(): void
    {
        $this->table = new TableStub($this->getDBManager());
        parent::setUp();
        $this->insertFixtures($this->table, self::getTableStubFixtures());
    }

    public function testInitializationSuccessful()
    {
        $tableSchema = $this->table->getTableSchema();
        $column = TableStub::B;
        $value = 'a string';
        $restriction = new TableRestriction($tableSchema, $column, $value);
        $this->assertEquals($tableSchema, $restriction->getTable());
        $this->assertEquals($column, $restriction->getColumnName());
        $this->assertEquals($value, $restriction->getColumnValue());
    }

    /**
     * @throws SchemaException
     * @throws DBALException
     */
    public function testInitializationWithNotExistingColumn()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionCode(SchemaException::COLUMN_DOESNT_EXIST);
        $tableSchema = $this->table->getTableSchema();
        new TableRestriction($tableSchema, 'yolo', 'c');
    }

    public function testCreate()
    {
        $tableSchema = $this->table->getTableSchema();
        $column = TableStub::C;
        $value = new \DateTimeImmutable('2007-08-31T16:47+00:00');
        $restriction = TableRestriction::create($tableSchema, $column, $value);
        $this->assertEquals($tableSchema, $restriction->getTable());
        $this->assertEquals($column, $restriction->getColumnName());
        $this->assertEquals($value, $restriction->getColumnValue());
    }
}
