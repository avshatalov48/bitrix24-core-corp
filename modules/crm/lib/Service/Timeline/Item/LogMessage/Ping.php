<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Model\ActivityPingOffsetsTable;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Date;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

class Ping extends LogMessage
{
	public function getType(): string
	{
		return 'Ping';
	}

	public function getIconCode(): ?string
	{
		return 'clock';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_PING_TITLE_NEW');
	}

	public function getContentBlocks(): ?array
	{
		$model = $this->getAssociatedEntityModel();
		$deadlineStr = $model->get('DEADLINE');
		if (empty($deadlineStr))
		{
			return [];
		}

		$created = $this->getDate();
		$deadline = DateTime::createFromUserTime($deadlineStr);
		$offset = $deadline->getDiff($created)->i; // minutes
		$offsetLists = ActivityPingOffsetsTable::getOffsetsByActivityId($this->getModel()->getAssociatedEntityId());

		return [
			'subject' => (new LineOfTextBlocks())
				->addContentBlock(
					'activity',
					(new Text())
						->setValue((string)$model->get('SUBJECT'))
						->setColor(Text::COLOR_BASE_90)
				)
				->addContentBlock(
					'time',
					(new Date())->setDate($deadline)->setColor(Text::COLOR_BASE_90)
				),
			'start' => ContentBlockFactory::createTitle($offset === 0 ? Loc::getMessage('CRM_TIMELINE_LOG_PING_ACTIVITY_STARTED_NEW') : Loc::getMessage('CRM_TIMELINE_LOG_PING_ACTIVITY_START_NEW', ['#OFFSET#' => $offsetLists[1]]))

		];
	}
}
