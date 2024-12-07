<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item;

use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group\SetOpenedChildAction;

final class RecurringItemGroupAction extends BaseItemGroupAction
{
	protected function prepareChildItems(): array
	{
		$actions = [];

		if ($this->canUpdateItemsInCategory())
		{
			$actions[] = new SetOpenedChildAction($this->factory->getEntityTypeId());
		}

		return $actions;
	}
}
