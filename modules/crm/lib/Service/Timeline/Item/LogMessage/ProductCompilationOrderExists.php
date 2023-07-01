<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class ProductCompilationOrderExists extends LogMessage
{
	public function getType(): string
	{
		return 'ProductCompilationOrderExists';
	}

	public function getTitle(): ?string
	{
		return null;
	}

	public function getDate(): ?DateTime
	{
		return null;
	}

	public function getAuthorId(): ?int
	{
		return null;
	}

	public function getContentBlocks(): ?array
	{
		return [
			'content' =>
				(new Text())
					->setValue(Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PRODUCT_SELECTION_DEAL_HAS_ORDER'))
					->setColor(Text::COLOR_BASE_90)
			,
		];
	}

	public function getIconCode(): ?string
	{
		return Icon::TASK;
	}
}
