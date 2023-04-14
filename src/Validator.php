<?php

declare(strict_types=1);

namespace Jtl\Connector\MappingTables;

use Doctrine\DBAL\ForwardCompatibility\Result;
use Jtl\Connector\Dbc\DbcRuntimeException;

class Validator
{
    /**
     * @param mixed       $value
     * @param string|null $name
     *
     * @return \Doctrine\DBAL\ForwardCompatibility\Result
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     */
    public static function returnResult($value, ?string $name = null): Result
    {
        if ($value instanceof Result) {
            return $value;
        }

        $name = $name ?? 'Variable';
        throw new DbcRuntimeException($name . ' must be instance of ' . Result::class . ' .');
    }

    /**
     * @param mixed       $value
     * @param string|null $name
     *
     * @return array
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     */
    public static function returnArray($value, ?string $name = null): array
    {
        if (\is_array($value)) {
            return $value;
        }

        $name = $name ?? 'Variable';
        throw new DbcRuntimeException($name . ' must be an array.');
    }
}
