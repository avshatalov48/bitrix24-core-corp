<?php

namespace Bitrix\Crm\Service\Router\Page\Item;

use Bitrix\Crm\Service\Router\Dto\SidePanelAnchorOptions;
use Bitrix\Crm\Service\Router\Dto\SidePanelAnchorRule;
use Bitrix\Crm\Service\Router\Route;

final class RecurListPage extends AbstractListPage
{
	private const COMPONENT_NAME = 'bitrix:crm.item.recurlist';

	protected function getComponentName(): string
	{
		return self::COMPONENT_NAME;
	}

	public static function routes(): array
	{
		return [
			(new Route('type/{entityTypeId}/list/recur/{categoryId}/'))
				->setRelatedComponent(self::COMPONENT_NAME)
			,
		];
	}

	public static function getSidePanelAnchorRules(): array
	{
		return [
			(new SidePanelAnchorRule("type/(\d+)/list/recur/(\d+)/$"))
				->scopes(self::scopes())
				->configureOptions(function (SidePanelAnchorOptions $options) {
					$options
						->setCacheable(false)
						->setAllowChangeHistory(true)
					;
				})
			,
		];
	}
}
