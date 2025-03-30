<?php

namespace Bitrix\Crm\Tour\EntityDetailsMenubar;

use Bitrix\Crm\Tour\BaseStubTour;
use Bitrix\Main\Localization\Loc;

class Whatsapp extends BaseStubTour
{
	public function getTitle(): string
	{
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/crm/timeline/menubar/config.php');

		return Loc::getMessage('CRM_TIMELINE_SMS_WHATSAPP_GUIDE_PROVIDER_ON_TITLE');
	}

	public function getText(): string
	{
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/crm/timeline/menubar/config.php');

		return Loc::getMessage('CRM_TIMELINE_SMS_WHATSAPP_GUIDE_PROVIDER_ON_TEXT_DEAL');
	}

	public function getOptionName(): string
	{
		return 'whatsapp';
	}
}
