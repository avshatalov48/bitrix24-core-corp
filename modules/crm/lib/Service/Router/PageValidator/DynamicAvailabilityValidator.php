<?php

namespace Bitrix\Crm\Service\Router\PageValidator;

use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Router\Contract\PageValidator;

final class DynamicAvailabilityValidator implements PageValidator
{
	public function isAvailable(): bool
	{
		return Container::getInstance()->getIntranetToolsManager()->checkDynamicAvailability();
	}

	public function showError(): void
	{
		echo AvailabilityManager::getInstance()->getDynamicInaccessibilityContent();
	}
}
