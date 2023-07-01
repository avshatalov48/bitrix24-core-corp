<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\Interfaces;
use Bitrix\Crm\Service\Timeline\Item\Mixin;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class PaymentPaid extends LogMessage implements Interfaces\HasPaymentDetailsContentBlock
{
	use Mixin\HasPaymentDetailsContentBlock;

	public function getType(): string
	{
		return 'PaymentPaid';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_CUSTOMER_PAYMENT_RECEIVED');
	}

	public function getIconCode(): ?string
	{
		return Icon::BANK_CARD;
	}

	public function getTags(): ?array
	{
		return [
			'status' => new Tag(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PAID'),
				Tag::TYPE_SUCCESS
			),
		];
	}

	public function getContentBlocks(): ?array
	{
		return [
			'details' => $this->getPaymentDetailsContentBlock(),
		];
	}
}
