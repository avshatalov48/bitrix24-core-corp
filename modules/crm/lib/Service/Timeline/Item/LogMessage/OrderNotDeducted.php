<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\Interfaces;
use Bitrix\Crm\Service\Timeline\Item\Mixin;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class OrderNotDeducted extends LogMessage implements Interfaces\HasOrderDetailsContentBlock
{
	use Mixin\HasOrderDetailsContentBlock;

	public function getType(): string
	{
		return 'OrderNotDeducted';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_ORDER_ENTITY_NAME');
	}

	public function getTags(): ?array
	{
		return [
			'status' => new Tag(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_NOT_DEDUCTED'),
				Tag::TYPE_SUCCESS
			),
		];
	}

	public function getIconCode(): ?string
	{
		return Icon::STORE;
	}

	public function getContentBlocks(): ?array
	{
		return [
			'details' => $this->getOrderDetailsContentBlock(),
		];
	}
}
