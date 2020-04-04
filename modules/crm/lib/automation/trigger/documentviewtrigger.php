<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DocumentViewTrigger extends DocumentCreateTrigger
{
	public static function getCode()
	{
		return 'DOCUMENT_VIEW';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_DOCUMENT_VIEW_NAME');
	}
}