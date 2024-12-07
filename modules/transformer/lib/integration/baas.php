<?php

namespace Bitrix\Transformer\Integration;

use Bitrix\Main\Loader;
use Bitrix\Transformer\Integration\Baas\DedicatedControllerFeature;
use Bitrix\Transformer\Integration\Baas\Feature;

/**
 * @internal
 */
final class Baas
{
	public static function isAvailable(): bool
	{
		return Loader::includeModule('baas');
	}

	public static function getDedicatedControllerFeature(): Feature
	{
		if (!self::isAvailable())
		{
			return new DedicatedControllerFeature(false, false, false);
		}

		$baasService = \Bitrix\Baas\ServiceManager::getInstance()->getByCode('documentgenerator_fast_transform');

		return new DedicatedControllerFeature(
			$baasService->isAvailable(),
			$baasService->isEnabled(),
			$baasService->isActive(),
		);
	}
}
