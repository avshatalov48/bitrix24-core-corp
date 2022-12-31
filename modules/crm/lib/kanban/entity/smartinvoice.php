<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Filter;
use Bitrix\Crm\Item;

class SmartInvoice extends Dynamic
{
	protected function getDefaultAdditionalSelectFields(): array
	{
		$fields = [];
		$fields[Item::FIELD_NAME_TITLE] = '';
		$fields[Item::FIELD_NAME_BEGIN_DATE] = '';
		$fields[Item::FIELD_NAME_CLOSE_DATE] = '';
		$fields[Item::FIELD_NAME_OPPORTUNITY] = '';
		$fields['CLIENT'] = '';

		return $fields;
	}

	public function getFilterPresets(): array
	{
		return (new Filter\Preset\SmartInvoice())
			->setDefaultValues($this->getFilter()->getDefaultFieldIDs())
			->setCategoryId($this->categoryId)
			->getDefaultPresets()
		;
	}
}
