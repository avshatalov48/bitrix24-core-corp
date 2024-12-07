<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\Interfaces;
use Bitrix\Crm\Service\Timeline\Item\Mixin;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Body;
use Bitrix\Crm\Service\Timeline\Layout\Common;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class OrderCheckPrinting extends LogMessage implements Interfaces\HasCheckDetails
{
	use Mixin\HasCheckDetails;

	public function getType(): string
	{
		return 'OrderCheckPrinting';
	}

	public function getTags(): ?array
	{
		return [
			'status' => new Tag(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PRINTING'),
				Tag::TYPE_WARNING
			)
		];
	}

	public function getTitle(): ?string
	{
		return
			Loc::getMessage('CRM_TIMELINE_ECOMMERCE_CHECK_ENTITY_NAME')
			. ' "' . $this->getAssociatedEntityModel()->get('NAME') . '"'
		;
	}

	public function getIconCode(): ?string
	{
		return Icon::CHECK;
	}

	public function getContentBlocks(): ?array
	{
		return [
			'details' => $this->getCheckDetailsContentBlock(),
		];
	}
}
