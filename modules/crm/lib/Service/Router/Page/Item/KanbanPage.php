<?php

namespace Bitrix\Crm\Service\Router\Page\Item;

use Bitrix\Crm\Service\Router\Route;

final class KanbanPage extends AbstractListPage
{
	private const COMPONENT_NAME = 'bitrix:crm.item.kanban';

	protected function getComponentName(): string
	{
		return self::COMPONENT_NAME;
	}

	public static function routes(): array
	{
		return [
			(new Route('type/{entityTypeId}/kanban/category/{categoryId}/'))
				->setRelatedComponent(self::COMPONENT_NAME)
			,
		];
	}
}
