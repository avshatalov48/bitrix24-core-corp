<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;

class Rest extends LogMessage
{
	public function getType(): string
	{
		return 'RestLog';
	}

	public function getIconCode(): ?string
	{
		return $this->getModel()->getSettings()['ICON_CODE'] ?? null;
	}

	public function getTitle(): ?string
	{
		return $this->getModel()->getSettings()['TITLE'] ?? null;
	}

	public function getContentBlocks(): ?array
	{
		return [
			'content' => (new LineOfTextBlocks())
				->addContentBlock(
					'data',
					(new Text())
						->setValue($this->getModel()->getSettings()['TEXT'])
						->setColor(Text::COLOR_BASE_90)
				),
		];
	}
}
