<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class CustomerProceededWithOrder extends LogMessage
{
	public function getType(): string
	{
		return 'CustomerProceededWithOrder';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_CUSTOMER_PROCEEDED_WITH_ORDER_TITLE');
	}

	public function getContentBlocks(): ?array
	{
		return [
			'content' =>
				(new Text())
					->setValue(Loc::getMessage('CRM_TIMELINE_ECOMMERCE_CUSTOMER_PROCEEDED_WITH_ORDER_MESSAGE'))
					->setColor(Text::COLOR_BASE_90)
			,
		];
	}

	public function getIconCode(): ?string
	{
		return Icon::TASK;
	}
}
