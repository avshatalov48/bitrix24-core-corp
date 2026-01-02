<?php

namespace Bitrix\Crm\Service\Router\Enum;

use Bitrix\Crm\Service\Container;

enum Scope
{
	case Crm;
	case Automation;
	case AutomatedSolution;
	case AutomatedSolutionWithoutPage;

	public function roots(): array
	{
		$router = Container::getInstance()->getRouter();

		return match ($this) {
			self::Crm => [
				$router->getDefaultRoot(),
			],
			self::Automation => [
				$router->getAutomationRoot(),
			],
			self::AutomatedSolution => $router->getCustomRoots(),
			self::AutomatedSolutionWithoutPage => $router->getCustomRootsWithoutPages(),
		};
	}
}
