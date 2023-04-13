<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc;

class DbcRuntimeException extends \RuntimeException
{
    public const
        TABLE_NOT_FOUND   = 10,
        TABLE_EMPTY       = 20,
        COLUMN_NOT_FOUND  = 30,
        CLASS_NOT_FOUND   = 40,
        CLASS_NOT_A_TABLE = 50,
        INDICES_MISSING   = 60,
        TABLE_NAME_EMPTY  = 70;

    /**
     * @param string $tableName
     *
     * @return DbcRuntimeException
     */
    public static function tableNotFound(string $tableName): self
    {
        return new self(\sprintf('Table with name %s not found', $tableName), self::TABLE_NOT_FOUND);
    }

    /**
     * @param string $tableName
     *
     * @return DbcRuntimeException
     */
    public static function tableEmpty(string $tableName): self
    {
        return new self(
            \sprintf('Table %s is empty. At least one column is required', $tableName),
            self::TABLE_EMPTY
        );
    }

    /**
     * @param string $columnName
     *
     * @return DbcRuntimeException
     */
    public static function columnNotFound(string $columnName): self
    {
        return new self(\sprintf('A Column with name %s could not get found', $columnName), self::COLUMN_NOT_FOUND);
    }

    /**
     * @param string $className
     *
     * @return DbcRuntimeException
     */
    public static function classNotFound(string $className): self
    {
        return new self(\sprintf('A class with name %s could not get found', $className), self::CLASS_NOT_FOUND);
    }

    /**
     * @param string $className
     *
     * @return DbcRuntimeException
     */
    public static function classNotChildOfTable(string $className): self
    {
        return new self(
            \sprintf('The class %s does not inherit from %s', $className, AbstractTable::class),
            self::CLASS_NOT_A_TABLE
        );
    }

    /**
     * @return DbcRuntimeException
     */
    public static function numericIndicesMissing(): self
    {
        return new self(
            'Converting a row with a subset of columns is only possible with associative indices',
            self::INDICES_MISSING
        );
    }

    /**
     * @return self
     */
    public static function tableNameEmpty(): self
    {
        return new self('Table name cannot be an empty string', self::TABLE_NAME_EMPTY);
    }
}
