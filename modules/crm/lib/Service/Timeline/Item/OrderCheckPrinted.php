<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/Ecommerce.php');

class OrderCheckPrinted extends OrderCheckPrintStatus
{
	public function getType(): string
	{
		return 'OrderCheckPrinted';
	}

	public function getTags(): ?array
	{
		return [
			'status' => new Tag(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PRINTED'),
				Tag::TYPE_SUCCESS
			)
		];
	}
}
