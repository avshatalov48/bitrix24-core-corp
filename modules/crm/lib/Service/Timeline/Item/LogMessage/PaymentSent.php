<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\Interfaces;
use Bitrix\Crm\Service\Timeline\Item\Mixin;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class PaymentSent extends LogMessage implements Interfaces\HasPaymentDetailsContentBlock
{
	use Mixin\HasPaymentDetailsContentBlock;

	public function getType(): string
	{
		return 'PaymentSent';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PAYMENT_ENTITY_NAME');
	}

	public function getIconCode(): ?string
	{
		return Icon::BANK_CARD;
	}

	public function getTags(): ?array
	{
		return [
			'status' => new Tag(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_SENT'),
				Tag::TYPE_SECONDARY
			),
		];
	}

	public function getContentBlocks(): ?array
	{
		$details = $this->getPaymentDetailsContentBlock();

		$sentViaContentBlock = $this->getSentViaContentBlock();
		if ($sentViaContentBlock)
		{
			$details->addContentBlock('sentVia', $sentViaContentBlock);
		}

		return [
			'details' => $details,
		];
	}

	private function getSentViaContentBlock(): ?ContentBlock
	{
		$fields = $this->getHistoryItemModel()->get('FIELDS');
		$fields = $fields ?? [];

		$destination = $fields['DESTINATION'] ?? '';
		if (!$destination)
		{
			return null;
		}

		$destinationMessage = Loc::getMessage('CRM_TIMELINE_ECOMMERCE_SENT_TO_DESTINATION_' . $destination);
		if (!$destinationMessage)
		{
			return null;
		}

		return
			(new Text())
				->setValue($destinationMessage)
				->setColor(Text::COLOR_BASE_90)
		;
	}
}
