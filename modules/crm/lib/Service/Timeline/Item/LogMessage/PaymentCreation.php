<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\Interfaces;
use Bitrix\Crm\Service\Timeline\Item\Mixin;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class PaymentCreation extends LogMessage implements
	Interfaces\HasPaymentDetailsContentBlock,
	Interfaces\HasPaymentMethodContentBlock
{
	use Mixin\HasPaymentDetailsContentBlock;
	use Mixin\HasPaymentMethodContentBlock;

	public function getType(): string
	{
		return 'PaymentCreation';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PAYMENT_CREATED');
	}

	public function getIconCode(): ?string
	{
		return Icon::STORE;
	}

	public function getContentBlocks(): ?array
	{
		return [
			'details' => $this->getPaymentDetailsContentBlock(),
			'paymentMethod' => $this->getPaymentMethodContentBlock(),
		];
	}
}
