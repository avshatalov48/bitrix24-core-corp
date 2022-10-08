<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\PhoneNumber;
use Bitrix\Main\Web\Uri;

class CallIncoming extends LogMessage
{
	public function getType(): string
	{
		return 'CallIncoming';
	}

	public function getIconCode(): ?string
	{
		return 'call';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_CALL_INCOMING_TITLE');
	}

	public function getContentBlocks(): ?array
	{
		$baseItem = $this->getHistoryItemModel()->get('BASE');
		$communication = $baseItem['ENTITY_INFO'] ?? [];
		if (empty($communication))
		{
			return [
				'content' => (new LineOfTextBlocks())
					->addContentBlock(
						'title',
						(new Text())
							->setValue(sprintf('%s:', Loc::getMessage('CRM_TIMELINE_LOG_CALL_INCOMING_CLIENT')))
							->setColor(Text::COLOR_BASE_70)
							->setFontSize(Text::FONT_SIZE_SM)
					)
					->addContentBlock(
						'data',
						(new Text())->setValue($this->getAssociatedEntityModel()->get('TITLE'))
							->setFontWeight(Text::FONT_WEIGHT_BOLD)
					)
			];
		}

		$formattedValue = empty($communication['SOURCE'])
			? ''
			: PhoneNumber\Parser::getInstance()->parse($communication['SOURCE'])->format();

		return [
			'content' => (new LineOfTextBlocks())
				->addContentBlock(
					'title',
					(new Text())
						->setValue(sprintf('%s:', Loc::getMessage('CRM_TIMELINE_LOG_CALL_INCOMING_CLIENT')))
						->setColor(Text::COLOR_BASE_70)
						->setFontSize(Text::FONT_SIZE_SM)
				)
				->addContentBlock(
					'data',
					ContentBlockFactory::createTextOrLink(
						sprintf('%s %s', $communication['TITLE'], $formattedValue),
						empty($communication['SHOW_URL'])
							? null
							: new Redirect(new Uri($communication['SHOW_URL']))
					)->setFontWeight(Text::FONT_WEIGHT_BOLD)
				)
		];
	}
}
