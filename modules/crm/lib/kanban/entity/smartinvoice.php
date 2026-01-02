<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Filter;
use Bitrix\Crm\Item;

class SmartInvoice extends Dynamic
{
	protected function getDefaultAdditionalSelectFields(): array
	{
		return [
			Item::FIELD_NAME_TITLE => '',
			Item::FIELD_NAME_BEGIN_DATE => '',
			Item::FIELD_NAME_CLOSE_DATE => '',
			Item::FIELD_NAME_OPPORTUNITY => '',
			'CLIENT' => '',
			'LAST_ACTIVITY_BY_TIME' => '',
			'LAST_ACTIVITY_BY_USER_AVATAR' => '',
		];
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
