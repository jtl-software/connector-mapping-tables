<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */
namespace jtl\Connector\MappingTables;

class DummyTable implements MappingTableInterface
{
    /**
     * @var integer
     */
    protected $type;

    /**
     * DummyTable constructor.
     */
    public function __construct()
    {
        $this->type = 0;
    }

    /**
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param integer $type
     * @return DummyTable
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param string $endpoint
     * @return integer
     */
    public function getHostId($endpoint)
    {
        return 0;
    }

    /**
     * @param integer $hostId
     * @param string|null $relationType
     * @return string
     */
    public function getEndpoint($hostId, $relationType = null)
    {
        return '';
    }

    /**
     * @param string $endpoint
     * @param integer $hostId
     * @return boolean
     */
    public function save($endpoint, $hostId)
    {
        return true;
    }

    /**
     * @param null $endpoint
     * @param null $hostId
     * @return boolean
     */
    public function remove($endpoint = null, $hostId = null)
    {
        return true;
    }

    /**
     * @return boolean
     */
    public function clear()
    {
        return true;
    }

    /**
     * @return integer
     */
    public function count()
    {
        return 0;
    }

    /**
     * @return array|string[]
     */
    public function findEndpoints(): array
    {
        return [];
    }

    /**
     * @param array $endpoints
     * @return array|string[]
     */
    public function findNotFetchedEndpoints(array $endpoints): array
    {
        return $endpoints;
    }

}
