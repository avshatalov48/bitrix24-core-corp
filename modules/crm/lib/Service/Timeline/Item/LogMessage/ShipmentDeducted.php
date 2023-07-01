<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\Interfaces;
use Bitrix\Crm\Service\Timeline\Item\Mixin;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class ShipmentDeducted extends LogMessage implements
	Interfaces\HasShipmentDetailsContentBlock,
	Interfaces\HasDeliveryMethodContentBlock
{
	use Mixin\HasShipmentDetailsContentBlock;
	use Mixin\HasDeliveryMethodContentBlock;

	public function getType(): string
	{
		return 'ShipmentDeducted';
	}

	public function getIconCode(): ?string
	{
		return Icon::STORE;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_SHIPMENT_ENTITY_NAME');
	}

	public function getContentBlocks(): ?array
	{
		return [
			'details' => $this->getShipmentDetailsContentBlock(),
			'deliveryMethod' => $this->getDeliveryMethodContentBlock(),
		];
	}

	public function getTags(): ?array
	{
		return [
			'status' => new Tag(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DEDUCTED'),
				Tag::TYPE_SUCCESS
			),
		];
	}
}
