<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\Interfaces;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Item\Mixin;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class PaymentError extends LogMessage implements
	Interfaces\HasPaymentDetailsContentBlock,
	Interfaces\HasPaymentMethodContentBlock
{
	use Mixin\HasPaymentDetailsContentBlock;
	use Mixin\HasPaymentMethodContentBlock;

	public function getType(): string
	{
		return 'PaymentError';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PAYMENT_ERROR');
	}

	public function getIconCode(): ?string
	{
		return Icon::ATTENTION;
	}

	public function getTags(): ?array
	{
		return [
			'status' => new Tag(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_ERROR'),
				Tag::TYPE_FAILURE
			),
		];
	}

	public function getContentBlocks(): ?array
	{
		$result = [
			'details' => $this->getPaymentDetailsContentBlock(),
			'paymentMethod' => $this->getPaymentMethodContentBlock(),
		];

		$fields = $this->getHistoryItemModel()->get('FIELDS');
		if (isset($fields['STATUS_DESCRIPTION']))
		{
			$errors = explode("\n", $fields['STATUS_DESCRIPTION']);
			if ($errors)
			{
				foreach ($errors as $index => $error)
				{
					$result['error' . $index] =
						(new Text())
							->setValue($error)
							->setColor(Text::COLOR_BASE_90)
					;
				}
			}
		}

		return $result;
	}
}
