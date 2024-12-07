<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\CalendarSharing;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Crm\Service\Timeline\Layout;

class Viewed extends LogMessage
{
	use CalendarSharing\ModelDataTrait;
	use CalendarSharing\SentContactContentBlockTrait;
	use CalendarSharing\MessageTrait;

	public function getType(): string
	{
		return 'CalendarSharingViewed';
	}

	public function getTitle(): ?string
	{
		return $this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_SLOTS_TITLE');
	}

	public function getTags(): ?array
	{
		return [
			'status' => new Tag(
				$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_VIEWED_TAG'),
				Tag::TYPE_PRIMARY
			)
		];
	}

	public function getIconCode(): ?string
	{
		return Icon::VIEW;
	}

	public function getContentBlocks(): ?array
	{
		if ($this->hasContact())
		{
			$content = $this->getSentContactContentBlock($this->getContactTypeId(), $this->getContactId(), $this->getTimestamp());
		}
		else
		{
			$content = new Layout\Body\ContentBlock\Text();
			$content
				->setValue($this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_GUEST_IS_VIEWING_SLOTS'))
				->setColor(Text::COLOR_BASE_70);
		}
		return [
			'sent' => $content,
		];
	}
}