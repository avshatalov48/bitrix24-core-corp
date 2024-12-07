<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item;

use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group\AssignChildAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group\MergeChildAction;
use Bitrix\Crm\Merger\EntityMergerFactory;

final class MyCompanyItemGroupAction extends BaseItemGroupAction
{
	protected function prepareChildItems(): array
	{
		if (!$this->canUpdateItemsInCategory())
		{
			return [];
		}

		$actions = [
			new AssignChildAction($this->factory->getEntityTypeId()),
		];

		if (
			$this->canDeleteItemsInCategory() // if the user has both update and delete permissions
			&& EntityMergerFactory::isEntityTypeSupported($this->factory->getEntityTypeId())
		)
		{
			$actions[] = new MergeChildAction($this->factory->getEntityTypeId());
		}

		return $actions;
	}
}
