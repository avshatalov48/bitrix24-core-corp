<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\StoreDocument\Modification;

use Bitrix\Crm\Service\Timeline\Item\LogMessage\StoreDocument\Modification;
use Bitrix\Main\Localization\Loc;

abstract class Field extends Modification
{
	public function getTitle(): ?string
	{
		return Loc::getMessage(
			sprintf(
				'CRM_TIMELINE_STORE_DOCUMENT_MODIFICATION_FIELD_TITLE_%s',
				$this->getConcreteTypeUpperCase()
			)
		);
	}
}
