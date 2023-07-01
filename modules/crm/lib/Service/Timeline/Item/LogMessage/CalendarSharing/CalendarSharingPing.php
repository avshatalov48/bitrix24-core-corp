<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\CalendarSharing;

use Bitrix\Crm\Service\Timeline\Item\LogMessage\Ping;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Date;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

class CalendarSharingPing extends Ping
{
	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_PING_TITLE_MEETING');
	}

	protected function buildContentBlocks(): array
	{
		$descriptionBlock = (new LineOfTextBlocks())
			->addContentBlock(
				'title',
				ContentBlockFactory::createTitle(
					Loc::getMessage('CRM_TIMELINE_LOG_PING_MEETING_WITH_CLIENT') . ','
				),
			)
			->addContentBlock(
				'date',
				(new Date())
					->setDate($this->getDeadline())
					->setColor(Text::COLOR_BASE_90)

			)
		;

		return [
			'subject' => $descriptionBlock,
		];
	}

	private function getDeadline(): DateTime
	{
		$this->entityModel = $this->getAssociatedEntityModel();
		$deadlineStr = $this->entityModel->get('DEADLINE');

		return DateTime::createFromUserTime($deadlineStr);
	}
}