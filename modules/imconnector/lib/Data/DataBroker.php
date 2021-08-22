<?php declare(strict_types=1);

namespace Bitrix\ImConnector\Data;

use Bitrix\Main;
use Bitrix\Main\ORM;

/**
 * Class data store.
 *
 * @package Bitrix\ImConnector\Data
 */
abstract class DataBroker
{
	/** @var self */
	//protected static $instance;

	/** @var array */
	protected $config = [];

	protected function __construct()
	{}

	protected function __clone()
	{}

	/**
	 * @return self
	 */
	public static function getInstance(): self
	{
		if (!isset(static::$instance))
		{
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Adds config of broker to locator.
	 *
	 * @param string $type
	 * @param string|\Closure $config
	 */
	public function register($config, string $type = 'default'): void
	{
		$this->config[$type] = $config;
	}

	/**
	 * Checks whether the broker with code exists.
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	public function has(string $type = 'default'): bool
	{
		return isset($this->config[$type]);
	}

	/**
	 * Returns broker by code.
	 *
	 * @param string $type
	 *
	 * @return ORM\Query\Query
	 * @throws Main\ObjectNotFoundException
	 */
	public function query(string $type = 'default'): ORM\Query\Query
	{
		if (!$this->has($type))
		{
			throw new Main\ObjectNotFoundException("Could not find data broker by code {$type}.");
		}

		if ($this->config[$type] instanceof \Closure)
		{
			$construct = $this->config[$type];
			$query = $construct();
		}
		else
		{
			/** @var Main\Entity\DataManager $class */
			$class = $this->config[$type];
			$query = $class::query();
		}

		return $query;
	}

	/**
	 * Returns entity object by it's primary id.
	 *
	 * @param $primaryId
	 * @param string $type
	 *
	 * return ORM\Query\Result
	 * @return ORM\Objectify\EntityObject|null
	 */
	public function get($primaryId, string $type = 'default')//: ?ORM\Objectify\EntityObject
	{
		if (!$this->has($type))
		{
			throw new Main\ObjectNotFoundException("Could not find data broker by code {$type}.");
		}

		if ($this->config[$type] instanceof \Closure)
		{
			$construct = $this->config[$type];
			$object = $construct($primaryId);
		}
		else
		{
			/** @var Main\Entity\DataManager $class */
			$class = $this->config[$type];
			$object = $class::getByPrimary($primaryId)->fetchObject();
		}

		return $object;
	}

	/**
	 * Returns empty entity object.
	 *
	 * @param string $type
	 *
	 * return ORM\Query\Result
	 * @return ORM\Objectify\EntityObject|null
	 */
	public function create(string $type = 'default')//: ?ORM\Objectify\EntityObject
	{
		if (!$this->has($type))
		{
			throw new Main\ObjectNotFoundException("Could not find data broker by code {$type}.");
		}

		if ($this->config[$type] instanceof \Closure)
		{
			$construct = $this->config[$type];
			$object = $construct();
		}
		else
		{
			/** @var Main\Entity\DataManager $class */
			$class = $this->config[$type];
			$object = $class::createObject();
		}

		return $object;
	}
}