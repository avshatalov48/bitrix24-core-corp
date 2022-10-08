<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Localization\Loc;

class OpenLineIncoming extends LogMessage
{
	public function getType(): string
	{
		return 'OpenLineIncoming';
	}

	public function getIconCode(): ?string
	{
		return 'IM';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_OL_INCOMING_TITLE');
	}

	public function getContentBlocks(): ?array
	{
		return [
			'link' => (new LineOfTextBlocks())
				->addContentBlock(
					'title',
					(new Text())
						->setValue(sprintf('%s:', Loc::getMessage('CRM_TIMELINE_LOG_TODO_CREATED_LINK')))
						->setColor(Text::COLOR_BASE_70)
						->setFontSize(Text::FONT_SIZE_SM)
				)
				->addContentBlock(
					'data',
					(new Text())->setValue('link with ...')
				),
			'date' => (new LineOfTextBlocks())
				->addContentBlock(
					'title',
					(new Text())
						->setValue(sprintf('%s:', Loc::getMessage('CRM_TIMELINE_LOG_TODO_CREATED_DATE')))
						->setColor(Text::COLOR_BASE_70)
						->setFontSize(Text::FONT_SIZE_SM)
				)
				->addContentBlock(
					'data',
					(new Text())->setValue('some date and time')
				),
			'description' => (new LineOfTextBlocks())
				->addContentBlock(
					'title',
					(new Text())
						->setValue(sprintf('%s:', Loc::getMessage('CRM_TIMELINE_LOG_TODO_CREATED_DESCRIPTION')))
						->setColor(Text::COLOR_BASE_70)
						->setFontSize(Text::FONT_SIZE_SM)
				)
				->addContentBlock(
					'data',
					(new Text())->setValue('some text ...')
				),
		];
	}
}
