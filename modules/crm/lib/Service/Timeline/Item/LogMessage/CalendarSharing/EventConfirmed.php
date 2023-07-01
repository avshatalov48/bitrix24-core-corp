<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\CalendarSharing;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Date;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;

class EventConfirmed extends LogMessage
{
	use CalendarSharing;

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
			'planned' => $this->getPlannedEventContentBlock(),
			'eventDate' => $this->getEventStartDateBlock(),
		];
	}
}