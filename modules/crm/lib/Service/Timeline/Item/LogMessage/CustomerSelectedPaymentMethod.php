<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\Interfaces;
use Bitrix\Crm\Service\Timeline\Item\Mixin;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class CustomerSelectedPaymentMethod extends LogMessage implements Interfaces\HasPaymentMethodContentBlock
{
	use Mixin\HasPaymentMethodContentBlock;

	public function getType(): string
	{
		return 'CustomerSelectedPaymentMethod';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_CUSTOMER_SELECTED_PAYMENT_METHOD');
	}

	public function getIconCode(): ?string
	{
		return Icon::BANK_CARD;
	}

	public function getContentBlocks(): ?array
	{
		return [
			'paymentMethod' => $this->getPaymentMethodContentBlock(),
		];
	}
}
