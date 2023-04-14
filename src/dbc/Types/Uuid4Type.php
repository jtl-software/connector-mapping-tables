<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc\Types;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

class Uuid4Type extends Type
{
    public const
        NAME = 'uuid4';

    /**
     * @param mixed[]          $column
     * @param AbstractPlatform $platform
     *
     * @return string
     * @throws Exception
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] = 16;
        $column['fixed']  = true;

        return $platform->getBinaryTypeDeclarationSQL($column);
    }

    /**
     * @param string           $value
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): string
    {
        return \ctype_xdigit(\str_replace('-', '', $value)) ? $value : \bin2hex($value);
    }

    /**
     * @param string           $value
     * @param AbstractPlatform $platform
     *
     * @return string
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        $converted = \hex2bin(\str_replace('-', '', $value));
        if ($converted === false) {
            throw ConversionException::conversionFailedInvalidType((string)$value, $this->getName(), ['UUIDv4 string']);
        }

        return $converted;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * Modifies the SQL expression (identifier, parameter) to convert to a PHP value.
     *
     * @param string           $sqlExpr
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function convertToPHPValueSQL($sqlExpr, $platform): string
    {
        if ($platform instanceof MySqlPlatform || $platform instanceof SqlitePlatform) {
            return $platform->getLowerExpression(\sprintf('HEX(%s)', $sqlExpr));
        }

        return $sqlExpr;
    }

    /**
     * @param AbstractPlatform $platform
     *
     * @return boolean
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    /**
     * @return boolean
     */
    public function canRequireSQLConversion(): bool
    {
        return true;
    }
}
