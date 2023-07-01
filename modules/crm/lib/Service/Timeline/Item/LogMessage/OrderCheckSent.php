<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\Interfaces;
use Bitrix\Crm\Service\Timeline\Item\Mixin;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class OrderCheckSent extends LogMessage implements Interfaces\HasCheckDetails
{
	use Mixin\HasCheckDetails;

	public function getType(): string
	{
		return 'OrderCheckSent';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_CHECK_SENT');
	}

	public function getIconCode(): ?string
	{
		return Icon::CHECK;
	}

	public function getContentBlocks(): ?array
	{
		return [
			'details' => $this->getCheckDetailsContentBlock(),
			'sentViaChat' => (new Text())->setValue(Loc::getMessage('CRM_TIMELINE_ECOMMERCE_SENT_VIA_CHAT'))
		];
	}
}
