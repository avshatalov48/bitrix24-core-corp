<?php

namespace Bitrix\SalesCenter\Integration;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

abstract class Base
{
	protected $isEnabled;
	protected static $instances = [];

	/**
	 * @return static
	 */
	public static function getInstance()
	{
		if(!isset(static::$instances[get_called_class()]))
		{
			static::$instances[get_called_class()] = new static();
		}

		return static::$instances[get_called_class()];
	}

	/**
	 * @return bool
	 */
	public function isEnabled()
	{
		return $this->isEnabled;
	}

	/**
	 * @return string
	 */
	abstract protected function getModuleName();

	/**
	 * @return bool
	 */
	protected function includeModule()
	{
		try
		{
			return Loader::includeModule($this->getModuleName());
		}
		catch(LoaderException $exception)
		{
			return false;
		}
	}

	protected function __construct()
	{
		$this->isEnabled = $this->includeModule();
	}
}