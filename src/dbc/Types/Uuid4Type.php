<?php


namespace Jtl\Connector\Dbc\Types;

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
     * @param array $column
     * @param AbstractPlatform $platform
     * @return mixed|string
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        $column['length'] = 16;
        $column['fixed'] = true;

        return $platform->getBinaryTypeDeclarationSQL($column);
    }

    /**
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return mixed|string
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return ctype_xdigit(str_replace('-', '', $value)) ? $value : bin2hex($value);
    }

    /**
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return mixed|string
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        $converted = hex2bin(str_replace('-', '', $value));
        if ($converted === false) {
            throw ConversionException::conversionFailedInvalidType((string)$value, $this->getName(), ['UUIDv4 string']);
        }

        return $converted;
    }

    /**
     * Modifies the SQL expression (identifier, parameter) to convert to a PHP value.
     *
     * @param string           $sqlExpr
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function convertToPHPValueSQL($sqlExpr, $platform)
    {
        if ($platform instanceof MySqlPlatform || $platform instanceof SqlitePlatform) {
            return $platform->getLowerExpression(sprintf('HEX(%s)', $sqlExpr));
        }

        return $sqlExpr;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param AbstractPlatform $platform
     * @return boolean
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }

    /**
     * @return boolean
     */
    public function canRequireSQLConversion()
    {
        return true;
    }
}
