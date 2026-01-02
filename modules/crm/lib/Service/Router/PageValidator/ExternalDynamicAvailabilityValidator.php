<?php

namespace Bitrix\Crm\Service\Router\PageValidator;

use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Router\Contract\PageValidator;

final class ExternalDynamicAvailabilityValidator implements PageValidator
{
	public function isAvailable(): bool
	{
		return Container::getInstance()->getIntranetToolsManager()->checkExternalDynamicAvailability();
	}

	public function showError(): void
	{
		echo AvailabilityManager::getInstance()->getExternalDynamicInaccessibilityContent();
	}
}
