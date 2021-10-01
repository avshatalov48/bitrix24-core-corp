<?php

namespace Bitrix\Crm\Timeline\HistoryDataModel\Presenter;

use Bitrix\Crm\Item;
use Bitrix\Crm\Timeline\HistoryDataModel\Presenter;
use Bitrix\Main\Localization\Loc;

class Modification extends Presenter
{
	protected function getHistoryTitle(string $fieldName = null): string
	{
		if ($fieldName === Item::FIELD_NAME_STAGE_ID)
		{
			return (string)Loc::getMessage('CRM_TIMELINE_PRESENTER_MODIFICATION_STAGE_ID');
		}
		if ($fieldName === Item::FIELD_NAME_CATEGORY_ID)
		{
			return (string)Loc::getMessage('CRM_TIMELINE_PRESENTER_MODIFICATION_CATEGORY_ID');
		}

		$fieldTitle = $this->entityImplementation->getFieldTitle((string)$fieldName) ?? $fieldName;

		return (string)Loc::getMessage(
			'CRM_TIMELINE_PRESENTER_MODIFICATION_BASE_TITLE',
			['#FIELD_NAME#' => $fieldTitle]
		);
	}
}
