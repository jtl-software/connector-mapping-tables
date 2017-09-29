<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace jtl\Connector\MappingTables;

use jtl\Connector\CDBC\TableException;

class MappingTableException extends TableException {
    const TABLE_TYPE_NOT_FOUND = 100;
    const COLUMN_DATA_MISSING = 110;
    const ENDPOINT_COLUMN_EXISTS = 120;
    const ENDPOINT_COLUMN_NOT_FOUND = 130;
    const ENDPOINT_COLUMNS_MISSING = 140;

    /**
     * @param string $type
     * @return MappingTableException
     */
    public static function tableTypeNotFound($type)
    {
        return new static('MappingTable for type ' . $type . ' not found!', self::TABLE_TYPE_NOT_FOUND);
    }

    /**
     * @param string $columnName
     * @return MappingTableException
     */
    public static function columnDataMissing($columnName)
    {
        return new static('Data for column ' . $columnName . ' is missing!', self::COLUMN_DATA_MISSING);
    }

    /**
     * @param string $columnName
     * @return MappingTableException
     */
    public static function columnExists($columnName)
    {
        return new static('Endpoint column with name ' . $columnName . ' exists!', self::ENDPOINT_COLUMN_EXISTS);
    }

    /**
     * @param string $columnName
     * @return MappingTableException
     */
    public static function columnNotFound($columnName)
    {
        return new static('Endpoint column with name ' . $columnName . ' is not defined!', self::ENDPOINT_COLUMN_NOT_FOUND);
    }

    /**
     * @return MappingTableException
     */
    public static function endpointColumnsNotDefined()
    {
        return new static('No endpoint columns are defined! There need to be at least one endpoint column!', self::ENDPOINT_COLUMNS_MISSING);
    }
}