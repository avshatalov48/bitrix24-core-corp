<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Item;

class SmartInvoice extends Dynamic
{
	protected function getDefaultAdditionalSelectFields(): array
	{
		$fields = [];
		$fields[Item::FIELD_NAME_TITLE] = '';
		$fields[Item\SmartInvoice::FIELD_NAME_ACCOUNT_NUMBER] = '';
		$fields[Item::FIELD_NAME_BEGIN_DATE] = '';
		$fields[Item::FIELD_NAME_OPPORTUNITY] = '';
		$fields['CLIENT'] = '';

		return $fields;
	}
}
