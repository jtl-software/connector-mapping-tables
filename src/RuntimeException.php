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
        EMPTY_ENDPOINT_ID = 100,
        WRONG_ENDPOINT_PARTS_AMOUNT = 110;

    /**
     * @param int $type
     * @return RuntimeException
     */
    public static function tableTypeNotFound(int $type): self
    {
        return new self('MappingTable for type ' . $type . ' not found', self::TABLE_TYPE_NOT_FOUND);
    }

    /**
     * @param string ...$columnNames
     * @return RuntimeException
     */
    public static function columnDataMissing(string ...$columnNames): self
    {
        return new self(sprintf('Data for column%s "%s" is missing', count($columnNames) > 1 ? 's' : '', implode('","', $columnNames)), self::COLUMN_DATA_MISSING);
    }

    /**
     * @param string $actualLength
     * @param string $expectedLength
     * @return RuntimeException
     */
    public static function wrongEndpointPartsAmount(string $actualLength, string $expectedLength): self
    {
        return new self(sprintf('Given endpoint parts (%d) do not match the expected amount (%d)', $actualLength, $expectedLength), self::WRONG_ENDPOINT_PARTS_AMOUNT);
    }

    /**
     * @param string $columnName
     * @return RuntimeException
     */
    public static function columnExists(string $columnName): self
    {
        return new self('Endpoint column with name ' . $columnName . ' exists', self::ENDPOINT_COLUMN_EXISTS);
    }

    /**
     * @param string $columnName
     * @return RuntimeException
     */
    public static function columnNotFound(string $columnName): self
    {
        return new self('Endpoint column with name ' . $columnName . ' is not defined', self::ENDPOINT_COLUMN_NOT_FOUND);
    }

    /**
     * @return RuntimeException
     */
    public static function endpointColumnsNotDefined(): self
    {
        return new self('No endpoint columns are defined! There need to be at least one endpoint column', self::ENDPOINT_COLUMNS_MISSING);
    }

    /**
     * @param int $type
     * @return RuntimeException
     */
    public static function typeNotFound(int $type): self
    {
        return new self(sprintf('Table is not responsible for type %s', $type), self::TYPE_NOT_FOUND);
    }

    /**
     * @return RuntimeException
     */
    public static function typesEmpty(): self
    {
        return new self('getTypes() must return an array which contains at least one integer value', self::TYPES_ARRAY_EMPTY);
    }

    /**
     * @return RuntimeException
     */
    public static function wrongTypes(): self
    {
        return new self('getTypes() must return an array with integer values only', self::TYPES_WRONG_DATA_TYPE);
    }

    /**
     * @param integer $type
     * @return RuntimeException
     */
    public static function unknownType(int $type): self
    {
        $msg = sprintf('Table is not responsible for this type (%s)', $type);
        return new self($msg, self::UNKNOWN_TYPE);
    }

    /**
     * @return RuntimeException
     */
    public static function emptyEndpointId(): self
    {
        return new self('Endpoint id is empty', static::EMPTY_ENDPOINT_ID);
    }
}
