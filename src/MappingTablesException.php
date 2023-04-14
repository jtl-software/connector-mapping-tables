<?php

declare(strict_types=1);

namespace Jtl\Connector\MappingTables;

class MappingTablesException extends \Exception
{
    public const
        TABLE_FOR_TYPE_NOT_FOUND       = 10,
        COLUMN_DATA_MISSING            = 20,
        ENDPOINT_COLUMN_EXISTS         = 30,
        ENDPOINT_COLUMN_NOT_FOUND      = 40,
        ENDPOINT_COLUMNS_MISSING       = 50,
        TYPES_ARRAY_EMPTY              = 60,
        TYPES_WRONG_DATA_TYPE          = 70,
        TABLE_NOT_RESPONSIBLE_FOR_TYPE = 80,
        EMPTY_ENDPOINT_ID              = 90,
        WRONG_ENDPOINT_PARTS_AMOUNT    = 100;

    /**
     * @param int $type
     *
     * @return MappingTablesException
     */
    public static function tableForTypeNotFound(int $type): self
    {
        return new self(
            \sprintf('No registered table is responsible for type %s', $type),
            self::TABLE_FOR_TYPE_NOT_FOUND
        );
    }

    /**
     * @param string ...$columnNames
     *
     * @return MappingTablesException
     */
    public static function columnDataMissing(string ...$columnNames): self
    {
        return new self(
            \sprintf(
                'Data for column%s "%s" missing',
                \count($columnNames) > 1 ? 's' : '',
                \implode('","', $columnNames)
            ),
            self::COLUMN_DATA_MISSING
        );
    }

    /**
     * @param integer $actualLength
     * @param integer $expectedLength
     *
     * @return MappingTablesException
     */
    public static function wrongEndpointPartsAmount(int $actualLength, int $expectedLength): self
    {
        return new self(
            \sprintf(
                'Given endpoint parts (%d) do not match the expected amount (%d)',
                $actualLength,
                $expectedLength
            ),
            self::WRONG_ENDPOINT_PARTS_AMOUNT
        );
    }

    /**
     * @param string $columnName
     *
     * @return MappingTablesException
     */
    public static function endpointColumnExists(string $columnName): self
    {
        return new self(
            \sprintf('Endpoint column with name %s already exists', $columnName),
            self::ENDPOINT_COLUMN_EXISTS
        );
    }

    /**
     * @param string $columnName
     *
     * @return MappingTablesException
     */
    public static function endpointColumnNotFound(string $columnName): self
    {
        return new self(
            \sprintf('Endpoint column with name %s is not defined', $columnName),
            self::ENDPOINT_COLUMN_NOT_FOUND
        );
    }

    /**
     * @return MappingTablesException
     */
    public static function endpointColumnsNotDefined(): self
    {
        return new self('At least one endpoint column has to be defined', self::ENDPOINT_COLUMNS_MISSING);
    }

    /**
     * @return MappingTablesException
     */
    public static function typesEmpty(): self
    {
        return new self(
            'getTypes() method must return an array of integer values with one or more fields',
            self::TYPES_ARRAY_EMPTY
        );
    }

    /**
     * @return MappingTablesException
     */
    public static function typeNotInteger(): self
    {
        return new self('getTypes() must return an array with integer values only', self::TYPES_WRONG_DATA_TYPE);
    }

    /**
     * @param int|null $type
     *
     * @return MappingTablesException
     */
    public static function tableNotResponsibleForType(?int $type): self
    {
        $msg = \sprintf('Table is not responsible for type %s', $type);
        return new self($msg, self::TABLE_NOT_RESPONSIBLE_FOR_TYPE);
    }

    /**
     * @param AbstractTable|null $mappingTable
     *
     * @return MappingTablesException
     */
    public static function emptyEndpointId(?AbstractTable $mappingTable = null): self
    {
        $message = 'There is a problem with the linking - Id\'s (Endpoint id is empty). If the problem is caused by
         a product or a category, try to resend the product (if it\'s a variant, send master and all children) or
         category. Perhaps try to do a fullexport.';

        if ($mappingTable !== null) {
            $message .= \sprintf(' The problem was caused by "%s".', \get_class($mappingTable));
        }

        return new self($message, static::EMPTY_ENDPOINT_ID);
    }
}
