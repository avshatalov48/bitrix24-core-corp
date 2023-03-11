<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\StoreDocument\Modification\Error;

use Bitrix\Crm\Service\Timeline\Item\LogMessage\StoreDocument\Modification;
use Bitrix\Main\Localization\Loc;

class Conduction extends Modification\Error
{
	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_STORE_DOCUMENT_CONDUCTION_ERROR_TITLE');
	}
}
