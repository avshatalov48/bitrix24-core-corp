<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service;

use Bitrix\Booking\Entity\ResourceType\ResourceType;
use Bitrix\Booking\Internals\Container;

class InstallAgent
{
	public static function execute(): string
	{
		$resourceTypeRepository = Container::getResourceTypeRepository();
		if (!$resourceTypeRepository->getList(limit: 1)->isEmpty())
		{
			return '';
		}

		$advTypes = Container::getAdvertisingTypeRepository()->getList();
		foreach ($advTypes as $advType)
		{
			$resourceTypeRepository->save(
				ResourceType::mapFromArray([
					...$advType['resourceType'],
					'moduleId' => ResourceType::INTERNAL_MODULE_ID,
				])
			);
		}

		return '';
	}
}
