<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */

namespace Jtl\Connector\Dbc;

class RuntimeException extends \RuntimeException
{
    public const
        TABLE_NOT_FOUND = 10,
        TABLE_EMPTY = 20,
        COLUMN_NOT_FOUND = 30,
        CLASS_NOT_FOUND = 40,
        CLASS_NOT_A_TABLE = 50,
        INDICES_MISSING = 60,
        TABLE_NAME_EMPTY = 70;

    /**
     * @param string $tableName
     * @return RuntimeException
     */
    public static function tableNotFound(string $tableName): self
    {
        return new static(sprintf('Table with name %s not found', $tableName), self::TABLE_NOT_FOUND);
    }

    /**
     * @param string $tableName
     * @return RuntimeException
     */
    public static function tableEmpty(string $tableName): self
    {
        return new static(sprintf('Table %s is empty. At least one column is required', $tableName), self::TABLE_EMPTY);
    }

    /**
     * @param string $columnName
     * @return RuntimeException
     */
    public static function columnNotFound(string $columnName): self
    {
        return new static(sprintf('A Column with name %s could not get found', $columnName), self::COLUMN_NOT_FOUND);
    }

    /**
     * @param string $className
     * @return RuntimeException
     */
    public static function classNotFound(string $className): self
    {
        return new static(sprintf('A class with name %s could not get found', $className), self::CLASS_NOT_FOUND);
    }

    /**
     * @param string $className
     * @return RuntimeException
     */
    public static function classNotChildOfTable(string $className): self
    {
        return new static(sprintf('The class %s does not inherit from %s', $className, AbstractTable::class), self::CLASS_NOT_A_TABLE);
    }

    /**
     * @return RuntimeException
     */
    public static function numericIndicesMissing(): self
    {
        return new static('Converting a row with a subset of columns is only possible with associative indices', self::INDICES_MISSING);
    }

    /**
     * @return static
     */
    public static function tableNameEmpty(): self
    {
        return new static('Table name cannot be an empty string', self::TABLE_NAME_EMPTY);
    }
}
