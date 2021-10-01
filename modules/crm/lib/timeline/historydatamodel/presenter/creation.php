<?php

namespace Bitrix\Crm\Timeline\HistoryDataModel\Presenter;

use Bitrix\Crm\Timeline\HistoryDataModel\Presenter;
use Bitrix\Main\Localization\Loc;

class Creation extends Presenter
{
	protected function getHistoryTitle(string $fieldName = null): string
	{
		return (string)Loc::getMessage('CRM_TIMELINE_PRESENTER_CREATION_TITLE');
	}
}
