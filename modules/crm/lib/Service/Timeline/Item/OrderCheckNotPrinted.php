<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/Ecommerce.php');

class OrderCheckNotPrinted extends OrderCheckPrintStatus
{
	public function getType(): string
	{
		return 'OrderCheckNotPrinted';
	}

	public function getTags(): ?array
	{
		return [
			'status' => new Tag(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PRINT_ERROR'),
				Tag::TYPE_FAILURE
			)
		];
	}
}
