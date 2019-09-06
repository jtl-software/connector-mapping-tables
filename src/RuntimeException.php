<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */

namespace Jtl\Connector\MappingTables;

class RuntimeException extends \RuntimeException
{
    const TABLE_TYPE_NOT_FOUND = 10;
    const COLUMN_DATA_MISSING = 20;
    const ENDPOINT_COLUMN_EXISTS = 30;
    const ENDPOINT_COLUMN_NOT_FOUND = 50;
    const ENDPOINT_COLUMNS_MISSING = 50;

    /**
     * @param int $type
     * @return RuntimeException
     */
    public static function tableTypeNotFound(int $type): RuntimeException
    {
        return new static('MappingTable for type ' . $type . ' not found!', self::TABLE_TYPE_NOT_FOUND);
    }

    /**
     * @param string $columnName
     * @return RuntimeException
     */
    public static function columnDataMissing(string $columnName): RuntimeException
    {
        return new static('Data for column ' . $columnName . ' is missing!', self::COLUMN_DATA_MISSING);
    }

    /**
     * @param string $columnName
     * @return RuntimeException
     */
    public static function columnExists(string $columnName): RuntimeException
    {
        return new static('Endpoint column with name ' . $columnName . ' exists!', self::ENDPOINT_COLUMN_EXISTS);
    }

    /**
     * @param string $columnName
     * @return RuntimeException
     */
    public static function columnNotFound(string $columnName): RuntimeException
    {
        return new static('Endpoint column with name ' . $columnName . ' is not defined!', self::ENDPOINT_COLUMN_NOT_FOUND);
    }

    /**
     * @return RuntimeException
     */
    public static function endpointColumnsNotDefined(): RuntimeException
    {
        return new static('No endpoint columns are defined! There need to be at least one endpoint column!', self::ENDPOINT_COLUMNS_MISSING);
    }
}
