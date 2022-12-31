<?php

namespace Bitrix\Crm\Engine\AutoWire;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Engine\AutoWire\ExactParameter;

final class Binder
{
	public static function registerDefaultAutoWirings(): void
	{
		\Bitrix\Main\Engine\AutoWire\Binder::registerGlobalAutoWiredParameter(new ExactParameter(
				Factory::class,
				'factory',
				static function ($className, ?int $entityTypeId = null, ?string $entityTypeName = null) {
					if ($entityTypeId === null && $entityTypeName !== null)
					{
						$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
					}

					if ($entityTypeId)
					{
						return Container::getInstance()->getFactory($entityTypeId);
					}

					return null;
				}
			)
		);

		\Bitrix\Main\Engine\AutoWire\Binder::registerGlobalAutoWiredParameter(new ExactParameter(
				Item::class,
				'entity',
				static function ($className, int $entityId, ?Factory $factory = null) {
					if ($factory)
					{
						return $factory->getItem($entityId);
					}

					return null;
				}
			)
		);
	}
}
