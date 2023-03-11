<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\StoreDocument\Modification;

use Bitrix\Crm\Service\Timeline\Item\LogMessage\StoreDocument\Modification;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Crm\Timeline\StoreDocumentStatusDictionary;
use Bitrix\Main\Localization\Loc;

abstract class Status extends Modification
{
	public function getTitle(): ?string
	{
		return Loc::getMessage(
			sprintf(
				'CRM_TIMELINE_STORE_DOCUMENT_MODIFICATION_STATUS_TITLE_%s',
				$this->getConcreteTypeUpperCase()
			)
		);
	}

	public function getTags(): ?array
	{
		$map = [
			StoreDocumentStatusDictionary::CONDUCTED => Tag::TYPE_SUCCESS,
			StoreDocumentStatusDictionary::DRAFT => Tag::TYPE_SECONDARY,
			StoreDocumentStatusDictionary::CANCELLED => Tag::TYPE_WARNING,
		];

		$status = $this->getHistoryItemModel()->get('STATUS_CLASS');
		$statusTitle = $this->getHistoryItemModel()->get('STATUS_TITLE');
		$tagStatus = $map[$status] ?? null;
		if (!$tagStatus || !$statusTitle)
		{
			return null;
		}

		return ['status' => new Tag($statusTitle, $tagStatus)];
	}
}
