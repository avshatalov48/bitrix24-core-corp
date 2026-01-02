<?php

namespace Bitrix\Crm\Field;

class QuoteId extends AbstractRelatedEntityField
{
	protected function getRelatedEntityTypeId(): int
	{
		return \CCrmOwnerType::Quote;
	}
}
