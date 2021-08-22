<?php

namespace Bitrix\Crm\Ads\Pixel\Configuration;

use Bitrix\Main\Config;
use Bitrix\Main\Web\Json;

/**
 * Class Configurator
 * @package Bitrix\Crm\Ads\Pixel\Configuration
 */
class Configurator
{
	protected const MODULE_ID = 'crm';

	protected const OPTION_ID_PREFIX = 'crm.conversion';

	protected function getModuleId() : string
	{
		return static::MODULE_ID;
	}

	protected function getConfigName(string $type) : string
	{
		return static::OPTION_ID_PREFIX . $type;
	}

	/**
	 * @param string $type
	 *
	 * @return Configuration|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function load(string $type) : ?Configuration
	{
		$data = Config\Option::get($this->getModuleId(), $this->getConfigName($type), null);
		if (!empty($data) && $data = Json::decode($data))
		{
			return new Configuration($data);
		}
		return null;
	}

	/**
	 * @param string $type
	 * @param Configuration $configuration
	 *
	 * @return bool
	 */
	public function save(string $type, Configuration $configuration) : bool
	{
		try
		{
			Config\Option::set($this->getModuleId(), $this->getConfigName($type), Json::encode($configuration));
		}
		catch (\Throwable $exception)
		{
			return false;
		}
		return true;
	}
}