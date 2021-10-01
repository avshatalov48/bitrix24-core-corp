<?php

namespace Bitrix\Crm\Timeline\HistoryDataModel\Presenter;

use Bitrix\Crm\Timeline\HistoryDataModel\Presenter;
use Bitrix\Main\Localization\Loc;

class Restoration extends Presenter
{
	protected function getHistoryTitle(string $fieldName = null): string
	{
		return (string)Loc::getMessage('CRM_TIMELINE_PRESENTER_RESTORATION_TITLE');
	}
}
