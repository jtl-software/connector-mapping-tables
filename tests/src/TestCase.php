<?php

declare(strict_types=1);

namespace Jtl\Connector\MappingTables;

abstract class TestCase extends \Jtl\Connector\Dbc\TestCase
{
    /**
     * @return array<int, array{'id1': int, 'id2': int, 'strg': string, 'identity_type': int, 'host_id': int}>
     */
    public static function getTableStubFixtures(): array
    {
        $data = [];

        $data[] = [
            "id1"           => 1,
            "id2"           => 1,
            "strg"          => "foo",
            "identity_type" => 815,
            "host_id"       => 3,
        ];

        $data[] = [
            "id1"           => 1,
            "id2"           => 2,
            "strg"          => "bar",
            "identity_type" => 7,
            "host_id"       => 2,
        ];

        $data[] = [
            "id1"           => 4,
            "id2"           => 2,
            "strg"          => "foobar",
            "identity_type" => 815,
            "host_id"       => 5,
        ];

        $data[] = [
            "id1"           => 6,
            "id2"           => 8,
            "strg"          => "yolo",
            "identity_type" => 815,
            "host_id"       => 5,
        ];

        return $data;
    }
}
