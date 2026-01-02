<?php

namespace Bitrix\Crm\Service\Router\PageValidator;

use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Router\Contract\PageValidator;

final class EntityTypeAvailabilityValidator implements PageValidator
{
	public function __construct(
		private readonly int $entityTypeId,
	)
	{
	}

	public function isAvailable(): bool
	{
		return Container::getInstance()
			->getIntranetToolsManager()
			->checkEntityTypeAvailability($this->entityTypeId)
		;
	}

	public function showError(): void
	{
		echo AvailabilityManager::getInstance()
			->getEntityTypeInaccessibilityContent($this->entityTypeId)
		;
	}
}
