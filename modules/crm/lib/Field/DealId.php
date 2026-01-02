<?php

namespace Bitrix\Crm\Field;


class DealId extends AbstractRelatedEntityField
{
	protected function getRelatedEntityTypeId(): int
	{
		return \CCrmOwnerType::Deal;
	}
}
