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
     * @param string $endpointId
     * @return integer
     */
    public function getHostId($endpointId)
    {
        return 0;
    }

    /**
     * @param integer $hostId
     * @return string
     */
    public function getEndpointId($hostId)
    {
        return '';
    }

    /**
     * @param string $endpointId
     * @param integer $hostId
     * @return boolean
     */
    public function save($endpointId, $hostId)
    {
        return true;
    }

    /**
     * @param null $endpointId
     * @param null $hostId
     * @return boolean
     */
    public function remove($endpointId = null, $hostId = null)
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
    public function findAllEndpoints(): array
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