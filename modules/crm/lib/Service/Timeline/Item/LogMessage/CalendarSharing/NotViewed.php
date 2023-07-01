<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\CalendarSharing;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout;

class NotViewed extends LogMessage
{
	use CalendarSharing;

	public function getType(): string
	{
		return 'CalendarSharingNotViewed';
	}

	public function getTitle(): ?string
	{
		return $this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_SLOTS_TITLE');
	}

	public function getTags(): ?array
	{
		return [
			'status' => new Tag(
				$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_NOT_VIEWED_TAG'),
				Tag::TYPE_SECONDARY
			)
		];
	}

	public function getIconCode(): ?string
	{
		return Icon::ATTENTION;
	}

	public function getContentBlocks(): ?array
	{
		if ($this->hasContact())
		{
			$content = $this->getSendedContactContentBlock();
		}
		else
		{
			$content = new Layout\Body\ContentBlock\Text();
			$content
				->setValue($this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_CLIENT_CAN_SET_MEETING'))
				->setColor(Text::COLOR_BASE_70);
		}
		return [
			'sent' => $content,
		];
	}
}