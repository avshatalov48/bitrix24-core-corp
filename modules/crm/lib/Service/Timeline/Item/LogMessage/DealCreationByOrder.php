<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\Interfaces;
use Bitrix\Crm\Service\Timeline\Item\Mixin;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Sale\EntityLinkBuilder;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class DealCreationByOrder extends LogMessage implements Interfaces\HasOrderDetailsContentBlock
{
	use Mixin\HasOrderDetailsContentBlock;

	public function getType(): string
	{
		return 'DealCreationByOrder';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DEAL_CREATED_SHOP_ORDER');
	}

	public function getContentBlocks(): ?array
	{
		return [
			'details' => $this->getOrderDetailsContentBlock([
				'LINK_CONTEXT' => (new EntityLinkBuilder\Context())->setIsShopLinkForced(true),
			]),
		];
	}
}
