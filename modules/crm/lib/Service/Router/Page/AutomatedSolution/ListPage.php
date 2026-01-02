<?php

namespace Bitrix\Crm\Service\Router\Page\AutomatedSolution;

use Bitrix\Crm\Service\Router\AbstractPage;
use Bitrix\Crm\Service\Router\Component\Component;
use Bitrix\Crm\Service\Router\Contract;
use Bitrix\Crm\Service\Router\Enum\Scope;
use Bitrix\Crm\Service\Router\PageValidator\ExternalDynamicAvailabilityValidator;
use Bitrix\Crm\Service\Router\Route;

final class ListPage extends AbstractPage
{
	private const COMPONENT_NAME = 'bitrix:crm.automated_solution.list';

	public function component(): Contract\Component
	{
		return new Component(
			name: self::COMPONENT_NAME,
		);
	}

	public static function routes(): array
	{
		return [
			(new Route('type/automated_solution/list/'))
				->setRelatedComponent(self::COMPONENT_NAME),
		];
	}

	public static function scopes(): array
	{
		return [
			Scope::Automation,
		];
	}

	protected function getPageValidators(): array
	{
		return [
			...parent::getPageValidators(),
			new ExternalDynamicAvailabilityValidator(),
		];
	}
}
