<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Item;

class QuoteController extends FactoryBasedController
{
	public const ADD_EVENT_NAME = 'timeline_quote_add';
	public const REMOVE_EVENT_NAME = 'timeline_quote_remove';
	public const RESTORE_EVENT_NAME = 'timeline_quote_restore';

	protected function getTrackedFieldNames(): array
	{
		return [
			Item::FIELD_NAME_TITLE,
			Item::FIELD_NAME_ASSIGNED,
			Item::FIELD_NAME_STAGE_ID,
			Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY,
		];
	}

	public function getEntityTypeID(): int
	{
		return \CCrmOwnerType::Quote;
	}
}
