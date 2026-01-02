<?php

namespace Bitrix\Crm\Service\Router\Page\Item;

use Bitrix\Crm\Service\Router\Route;

final class ListPage extends AbstractListPage
{
	private const COMPONENT_NAME = 'bitrix:crm.item.list';

	protected function getComponentName(): string
	{
		return self::COMPONENT_NAME;
	}

	public static function routes(): array
	{
		return [
			(new Route('type/{entityTypeId}/list/category/{categoryId}/'))
				->setRelatedComponent(self::COMPONENT_NAME)
			,
		];
	}
}
