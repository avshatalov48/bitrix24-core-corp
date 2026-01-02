<?php

namespace Bitrix\Crm\Service\Router\PageValidator;

use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Router\Contract\PageValidator;
use Bitrix\Crm\Service\Router\Enum\Scope;

final class ScopeAvailabilityValidator implements PageValidator
{
	public function __construct(
		private readonly ?Scope $scope,
	)
	{
	}

	public function isAvailable(): bool
	{
		$toolsManager = Container::getInstance()->getIntranetToolsManager();

		return match ($this->scope) {
			Scope::Crm => $toolsManager->checkCrmAvailability(),
			Scope::AutomatedSolution,
			Scope::AutomatedSolutionWithoutPage => $toolsManager->checkExternalDynamicAvailability(),
			Scope::Automation => $toolsManager->checkOnlyBizprocAvailability(),
			default => true,
		};
	}

	public function showError(): void
	{
		$availabilityManager = AvailabilityManager::getInstance();

		$content = match ($this->scope) {
			Scope::Crm => $availabilityManager->getCrmInaccessibilityContent(),
			Scope::AutomatedSolution,
			Scope::AutomatedSolutionWithoutPage => $availabilityManager->getExternalDynamicInaccessibilityContent(),
			Scope::Automation => $availabilityManager->getBizprocInaccessibilityContent(),
			default => null,
		};

		if ($content !== null)
		{
			echo $content;
		}
	}
}
