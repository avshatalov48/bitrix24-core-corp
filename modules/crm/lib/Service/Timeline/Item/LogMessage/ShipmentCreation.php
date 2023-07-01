<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\Interfaces;
use Bitrix\Crm\Service\Timeline\Item\Mixin;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class ShipmentCreation extends LogMessage implements
	Interfaces\HasShipmentDetailsContentBlock,
	Interfaces\HasDeliveryMethodContentBlock
{
	use Mixin\HasShipmentDetailsContentBlock;
	use Mixin\HasDeliveryMethodContentBlock;

	public function getType(): string
	{
		return 'ShipmentCreation';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_SHIPMENT_CREATED');
	}

	public function getIconCode(): ?string
	{
		return Icon::STORE;
	}

	public function getContentBlocks(): ?array
	{
		return [
			'details' => $this->getShipmentDetailsContentBlock(),
			'deliveryMethod' => $this->getDeliveryMethodContentBlock(),
		];
	}
}
