<?php

namespace Bitrix\Crm\Service\Router\Page\Item;

use Bitrix\Crm\Service\Router\AbstractPage;
use Bitrix\Crm\Service\Router\Component\Component;
use Bitrix\Crm\Service\Router\Contract;
use Bitrix\Crm\Service\Router\Enum\Scope;
use Bitrix\Crm\Service\Router\Route;

final class DeadlinesPage extends AbstractListPage
{
	private const COMPONENT_NAME = 'bitrix:crm.item.deadlines';

	protected function getComponentName(): string
	{
		return self::COMPONENT_NAME;
	}

	public static function routes(): array
	{
		return [
			(new Route('type/{entityTypeId}/deadlines/category/{categoryId}/'))
				->setRelatedComponent(self::COMPONENT_NAME)
			,
		];
	}
}
