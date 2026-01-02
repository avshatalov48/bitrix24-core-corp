<?php

namespace Bitrix\Crm\Service\Router\Page\Type;

use Bitrix\Crm\Service\Router\AbstractPage;
use Bitrix\Crm\Service\Router\Component\Component;
use Bitrix\Crm\Service\Router\Contract;
use Bitrix\Crm\Service\Router\Enum\Scope;
use Bitrix\Crm\Service\Router\PageValidator\DynamicAvailabilityValidator;
use Bitrix\Crm\Service\Router\PageValidator\ExternalDynamicAvailabilityValidator;
use Bitrix\Crm\Service\Router\Route;

final class ListPage extends AbstractPage
{
	private const COMPONENT_NAME = 'bitrix:crm.type.list';

	public function component(): Contract\Component
	{
		return new Component(self::COMPONENT_NAME);
	}

	public static function routes(): array
	{
		return [
			(new Route('type/'))
				->setRelatedComponent(self::COMPONENT_NAME)
			,
		];
	}

	public static function scopes(): array
	{
		return [
			Scope::Crm,
			Scope::Automation,
		];
	}

	protected function getPageValidators(): array
	{
		$validators = parent::getPageValidators();

		if ($this->currentScope === Scope::Automation)
		{
			$validators[] = new ExternalDynamicAvailabilityValidator();
		}

		$dynamicAvailabilityValidator = match ($this->currentScope) {
			Scope::Crm => new DynamicAvailabilityValidator(),
			Scope::Automation => new ExternalDynamicAvailabilityValidator(),
			default => null,
		};

		if ($dynamicAvailabilityValidator !== null)
		{
			$validators[] = $dynamicAvailabilityValidator;
		}

		return $validators;
	}
}
