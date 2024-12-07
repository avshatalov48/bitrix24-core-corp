<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action;

use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\CallList\AddItemsToCallListAction;
use Bitrix\Crm\Component\EntityList\Grid\Settings\ItemSettings;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Grid\Panel\Action\DataProvider;
use Bitrix\Main\Grid\Panel\Action\ForAllCheckboxAction;

final class CallListUpdateModeItemDataProvider extends DataProvider
{
	public function __construct(
		private int $entityTypeId,
		private int $callListId,
		private string $callListContext,
		ItemSettings $settings,
	)
	{
		if ($this->callListId <= 0)
		{
			throw new ArgumentOutOfRangeException('callListId', 1);
		}

		if (empty($this->callListContext))
		{
			throw new ArgumentNullException('callListContext');
		}

		parent::__construct($settings);
	}

	public function prepareActions(): array
	{
		return [
			new AddItemsToCallListAction($this->entityTypeId, $this->callListId, $this->callListContext),
			new ForAllCheckboxAction(),
		];
	}
}
