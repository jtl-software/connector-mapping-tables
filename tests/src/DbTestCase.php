<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2016 JTL-Software GmbH
 */

namespace Jtl\Connector\MappingTables;

use Doctrine\DBAL\DBALException;
use Jtl\Connector\Dbc\DbManager;
use Jtl\Connector\Dbc\DbManagerStub;
use PHPUnit\Framework\TestCase;

abstract class DbTestCase extends \Jtl\Connector\Dbc\DbTestCase
{
    /**
     * @return mixed[]
     */
    public static function getTableStubFixtures(): array
    {
        $data = [];

        $data[] = [
            "id1" => 1,
            "id2" => 1,
            "strg" => "foo",
            "identity_type" => 815,
            "host_id" => 3,
        ];

        $data[] = [
            "id1" => 1,
            "id2" => 2,
            "strg" => "bar",
            "identity_type" => 7,
            "host_id" => 2,
        ];

        $data[] = [
            "id1" => 4,
            "id2" => 2,
            "strg" => "foobar",
            "identity_type" => 815,
            "host_id" => 5,
        ];

        $data[] = [
            "id1" => 6,
            "id2" => 8,
            "strg" => "yolo",
            "identity_type" => 815,
            "host_id" => 5,
        ];

        return $data;
    }
}
