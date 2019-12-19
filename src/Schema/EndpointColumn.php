<?php
namespace Jtl\Connector\MappingTables\Schema;

class EndpointColumn
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var mixed[]
     */
    protected $options;

    /**
     * @var boolean
     */
    protected $isPrimary = true;

    /**
     * EndpointColumn constructor.
     * @param string $name
     * @param string $type
     * @param mixed[] $options
     * @param bool $isPrimary
     */
    public function __construct(string $name, string $type, array $options, bool $isPrimary = true)
    {
        $this->name = $name;
        $this->type = $type;
        $this->options = $options;
        $this->isPrimary = $isPrimary;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return mixed[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return boolean
     */
    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    /**
     * @param string $name
     * @param string $type
     * @param array $options
     * @param boolean $isPrimary
     * @return EndpointColumn
     */
    public static function create(string $name, string $type, array $options, bool $isPrimary = true): EndpointColumn
    {
        return new static($name, $type, $options, $isPrimary);
    }
}