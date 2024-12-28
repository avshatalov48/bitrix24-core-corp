<?php

namespace Bitrix\HumanResources\Config;

use Bitrix\HumanResources\Trait\SingletonTrait;
use Bitrix\Main;
use Bitrix\Main\Application;

class Feature
{
	use SingletonTrait;

	private const MODULE_NAME = 'humanresources';

	public function isHcmLinkAvailable(): bool
	{
		$regionCode = Application::getInstance()->getLicense()->getRegion();

		return in_array($regionCode, ['ru'], true);
	}

	private function getOptionValue(string $option, mixed $defaultValue)
	{
		return Main\Config\Option::get(self::MODULE_NAME, $option, $defaultValue);
	}
}