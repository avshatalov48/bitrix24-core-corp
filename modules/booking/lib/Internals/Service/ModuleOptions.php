<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service;

use Bitrix\Main\Config\Option;

class ModuleOptions
{
	public const FIRST_RESOURCE_ADDED_OPTION = 'first_resource_added';
	private const MODULE_ID = 'booking';

	public static function handleResourceAdded(): void
	{
		if (self::isFirstResourceAdded())
		{
			return;
		}

		self::setFirstResourceAdded();
	}

	private static function isFirstResourceAdded(): bool
	{
		return (bool)Option::get(moduleId: self::MODULE_ID, name: self::FIRST_RESOURCE_ADDED_OPTION, default: false);
	}

	private static function setFirstResourceAdded(): void
	{
		Option::set(moduleId: self::MODULE_ID, name: self::FIRST_RESOURCE_ADDED_OPTION, value: true);
	}
}
