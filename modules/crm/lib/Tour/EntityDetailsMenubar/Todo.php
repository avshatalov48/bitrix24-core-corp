<?php

namespace Bitrix\Crm\Tour\EntityDetailsMenubar;

use Bitrix\Crm\Tour\BaseStubTour;
use Bitrix\Main\Localization\Loc;

class Todo extends BaseStubTour
{
	public function getTitle(): string
	{
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/crm/timeline/menubar/config.php');

		return Loc::getMessage('CRM_TIMELINE_TODO_GUIDE_TITLE');
	}

	public function getText(): string
	{
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/crm/timeline/menubar/config.php');

		return Loc::getMessage('CRM_TIMELINE_TODO_GUIDE_TEXT');
	}

	public function getOptionName(): string
	{
		return 'todo';
	}
}
