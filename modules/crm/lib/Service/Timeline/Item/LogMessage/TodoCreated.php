<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Localization\Loc;

class TodoCreated extends LogMessage
{
	public function getType(): string
	{
		return 'TodoCreated';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_TODO_CREATED_TITLE');
	}

	public function getContentBlocks(): ?array
	{
		return [
			'content' => (new LineOfTextBlocks())
				->addContentBlock(
					'title',
					(new Text())
						->setValue(sprintf('%s:', Loc::getMessage('CRM_TIMELINE_LOG_OL_INCOMING_CHANNEL')))
						->setColor(Text::COLOR_BASE_70)
						->setFontSize(Text::FONT_SIZE_SM)
				)
				->addContentBlock(
					'data',
					(new Text())->setValue('Channel name')->setFontWeight(Text::FONT_WEIGHT_BOLD)
				)
		];
	}
}
