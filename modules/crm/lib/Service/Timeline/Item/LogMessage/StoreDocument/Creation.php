<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\StoreDocument;

use Bitrix\Main\Localization\Loc;

abstract class Creation extends Base
{
	public function getType(): string
	{
		return sprintf(
			'StoreDocument%s:Creation',
			$this->getConcreteType()
		);
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage(
			sprintf(
				'CRM_TIMELINE_STORE_DOCUMENT_CREATION_TITLE_%s',
				$this->getConcreteTypeUpperCase()
			)
		);
	}
}
