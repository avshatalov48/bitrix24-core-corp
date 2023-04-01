<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Timeline\TimelineMarkType;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class OrderSynchronization extends LogMessage
{
	public function getType(): string
	{
		return 'OrderSynchronization';
	}

	public function getTitle(): ?string
	{
		if ($this->model->getTypeCategoryId() === TimelineMarkType::SUCCESS)
		{
			return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_SYNC_SUCCESS');
		}

		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_SYNC_FAILURE');
	}

	public function getContentBlocks(): ?array
	{
		$message = $this->getHistoryItemModel()->get('MESSAGE') ?? '';

		return [
			'content' =>
				(new Text())
					->setValue((string)$message)
					->setColor(Text::COLOR_BASE_90)
			,
		];
	}
}
