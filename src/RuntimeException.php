<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace Jtl\Connector\MappingTables;

class RuntimeException extends \RuntimeException
{
    public const
        TABLE_TYPE_NOT_FOUND = 10,
        COLUMN_DATA_MISSING = 20,
        ENDPOINT_COLUMN_EXISTS = 30,
        ENDPOINT_COLUMN_NOT_FOUND = 50,
        ENDPOINT_COLUMNS_MISSING = 50,
        TYPE_NOT_FOUND = 60,
        TYPES_ARRAY_EMPTY = 70,
        TYPES_WRONG_DATA_TYPE = 80,
        UNKNOWN_TYPE = 90,
        EMPTY_ENDPOINT_ID = 100;

    /**
     * @param int $type
     * @return RuntimeException
     */
    public static function tableTypeNotFound(int $type): RuntimeException
    {
        return new static('MappingTable for type ' . $type . ' not found', self::TABLE_TYPE_NOT_FOUND);
    }

    /**
     * @param string $columnName
     * @return RuntimeException
     */
    public static function columnDataMissing(string $columnName): RuntimeException
    {
        return new static('Data for column ' . $columnName . ' is missing', self::COLUMN_DATA_MISSING);
    }

    /**
     * @param string $columnName
     * @return RuntimeException
     */
    public static function columnExists(string $columnName): RuntimeException
    {
        return new static('Endpoint column with name ' . $columnName . ' exists', self::ENDPOINT_COLUMN_EXISTS);
    }

    /**
     * @param string $columnName
     * @return RuntimeException
     */
    public static function columnNotFound(string $columnName): RuntimeException
    {
        return new static('Endpoint column with name ' . $columnName . ' is not defined', self::ENDPOINT_COLUMN_NOT_FOUND);
    }

    /**
     * @return RuntimeException
     */
    public static function endpointColumnsNotDefined(): RuntimeException
    {
        return new static('No endpoint columns are defined! There need to be at least one endpoint column', self::ENDPOINT_COLUMNS_MISSING);
    }

    /**
     * @param int $type
     * @return RuntimeException
     */
    public static function typeNotFound(int $type): RuntimeException
    {
        return new static(sprintf('Table is not responsible for type %s', $type), self::TYPE_NOT_FOUND);
    }

    /**
     * @return RuntimeException
     */
    public static function typesEmpty(): RuntimeException
    {
        return new static('getTypes() must return an array which contains at least one integer value', self::TYPES_ARRAY_EMPTY);
    }

    /**
     * @return RuntimeException
     */
    public static function wrongTypes(): RuntimeException
    {
        return new static('getTypes() must return an array with integer values only', self::TYPES_WRONG_DATA_TYPE);
    }

    /**
     * @param integer $type
     * @return RuntimeException
     */
    public static function unknownType(int $type): RuntimeException
    {
        $msg = sprintf('Table is not responsible for this type (%s)', $type);
        return new static($msg, self::UNKNOWN_TYPE);
    }

    /**
     * @return RuntimeException
     */
    public static function emptyEndpointId(): RuntimeException
    {
        return new static('Endpoint id is empty', static::EMPTY_ENDPOINT_ID);
    }
}
