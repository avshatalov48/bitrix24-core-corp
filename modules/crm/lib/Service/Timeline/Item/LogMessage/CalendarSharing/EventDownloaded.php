<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\CalendarSharing;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;

class EventDownloaded extends LogMessage
{
	use CalendarSharing\ModelDataTrait;
	use CalendarSharing\PlannedEventContentBlockTrait;
	use CalendarSharing\MessageTrait;

	public function getType(): string
	{
		return 'CalendarSharingEventDownloaded';
	}

	public function getTitle(): ?string
	{
		return $this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_NEWS_ON_MEETING_TITLE');
	}

	public function getTags(): ?array
	{
		return [
			'status' => new Tag(
				$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_EVENT_DOWNLOADED_TAG'),
				Tag::TYPE_SUCCESS
			)
		];
	}

	public function getContentBlocks(): ?array
	{
		return [
			'planned' => $this->getPlannedEventContentBlock($this->getContactTypeId(), $this->getContactId()),
		];
	}
}