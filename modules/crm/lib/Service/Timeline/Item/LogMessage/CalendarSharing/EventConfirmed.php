<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\CalendarSharing;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Date;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;

class EventConfirmed extends LogMessage
{
	use CalendarSharing\DateTrait;
	use CalendarSharing\ModelDataTrait;
	use CalendarSharing\PlannedEventContentBlockTrait;
	use CalendarSharing\MessageTrait;

	public function getType(): string
	{
		return 'CalendarSharingEventConfirmed';
	}

	public function getTitle(): ?string
	{
		return $this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_MEETING_CONFIRMED_TITLE');
	}

	public function getTags(): ?array
	{
		return [
			'status' => new Tag(
				$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_APPOINTED_TAG'),
				Tag::TYPE_SUCCESS
			)
		];
	}

	public function getContentBlocks(): ?array
	{

		return [
			'planned' => $this->getPlannedEventContentBlock($this->getContactTypeId(), $this->getContactId()),
			'eventDate' => $this->getEventStartDateBlock(),
		];
	}

	public function getEventStartDateBlock(): ContentBlock
	{
		$result = new LineOfTextBlocks();

		$result->addContentBlock('title',
			ContentBlockFactory::createTitle(
				$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_DATE_AND_TIME_EVENT') . ':'
			)
		);

		$date = $this->getDateContent($this->getTimestamp());
		$result->addContentBlock(
			'linkCreationDate',
			(new Date())
				->setDate($date)
				->setColor(Text::COLOR_BASE_90)
		);

		return $result;
	}
}