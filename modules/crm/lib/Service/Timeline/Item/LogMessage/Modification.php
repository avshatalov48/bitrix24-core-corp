<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ValueChange;

class Modification extends LogMessage
{
	public function getIconCode(): ?string
	{
		$modifiedField = $this->getHistoryItemModel()->get('MODIFIED_FIELD');

		switch ($modifiedField)
		{
			case 'STATUS_ID':
			case 'STAGE_ID':
			case 'STATUS':
			case 'TASK:STATUS':
				return 'stage-change';
			case 'IS_MANUAL_OPPORTUNITY':
				return 'sum';
		}

		return parent::getIconCode();
	}

	public function getType(): string
	{
		return 'Modification';
	}

	public function getTitle(): ?string
	{
		return $this->getHistoryItemModel()->get('TITLE');
	}

	public function getContentBlocks(): ?array
	{
		$startName = $this->getHistoryItemModel()->get('START_NAME');
		$finishName = $this->getHistoryItemModel()->get('FINISH_NAME');

		return [
			'valueChange' => (new ValueChange())
				->setFrom($startName)
				->setTo($finishName),
		];
	}
}
