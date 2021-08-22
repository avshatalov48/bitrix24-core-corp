<?php

namespace Bitrix\Crm\Ads\Pixel\Configuration;

/**
 * Class Configuration
 * @package Bitrix\Crm\Ads\Pixel\Configuration
 */
class Configuration extends \ArrayObject implements \JsonSerializable
{
	/**
	 * Configuration constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config = [])
	{
		parent::__construct($config ,\ArrayObject::STD_PROP_LIST);
	}


	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	public function has(string $id) : bool
	{
		return $this->offsetExists($id);
	}

	/**
	 * @param string $id
	 * @param $value
	 *
	 * @return Configuration
	 */
	public function set(string $id, $value) : Configuration
	{
		$this->offsetSet($id,$value);
		return $this;
	}

	/**
	 * @param string $id
	 *
	 * @return mixed|null
	 */
	public function get(string $id)
	{
		if ($this->has($id))
		{
			return $this->offsetGet($id);
		}
		return null;
	}

	/**
	 * @return array
	 */
	public function toArray() : array
	{
		return $this->getArrayCopy();
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize() : array
	{
		return $this->toArray();
	}
}