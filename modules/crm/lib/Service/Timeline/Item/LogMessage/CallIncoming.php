<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Client;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

class CallIncoming extends LogMessage
{
	public function getType(): string
	{
		return 'CallIncoming';
	}

	public function getIconCode(): ?string
	{
		return Icon::CALL;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_CALL_INCOMING_TITLE');
	}

	public function getContentBlocks(): ?array
	{
		$clientBlock = $this->buildClientBlock(Client::BLOCK_WITH_FORMATTED_VALUE);
		if (isset($clientBlock))
		{
			return [
				'content' => $clientBlock
			];
		}

		// if communications not found - show title
		$title = (string)$this->getAssociatedEntityModel()->get('TITLE');

		return [
			'content' => (new LineOfTextBlocks())
				->addContentBlock(
					'title',
					ContentBlockFactory::createTitle(Loc::getMessage('CRM_TIMELINE_LOG_CALL_INCOMING_CLIENT'))
				)
				->addContentBlock('data', (new Text())->setValue($title)->setColor(Text::COLOR_BASE_90))
		];
	}
}
