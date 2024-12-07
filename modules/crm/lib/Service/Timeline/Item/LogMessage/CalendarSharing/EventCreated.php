<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\CalendarSharing;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Date;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;

class EventCreated extends LogMessage
{
	use CalendarSharing\ModelDataTrait;
	use CalendarSharing\DateTrait;
	use CalendarSharing\ContactTrait;
	use CalendarSharing\MessageTrait;

	public function getType(): string
	{
		return 'CalendarSharingEventCreated';
	}

	public function getTitle(): ?string
	{
		return $this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_MEETING_PLANNED_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Icon::CIRCLE_CHECK;
	}

	public function getContentBlocks(): ?array
	{
		$contactName = $this->getContactName($this->getContactTypeId(), $this->getContactId());
		$contactUrl = $this->getContactUrl($this->getContactTypeId(), $this->getContactId());
		$date = $this->getDateContent($this->getTimestamp());

		$contentBlock = (new LineOfTextBlocks())
			->addContentBlock(
				'contactInfo',
				ContentBlockFactory::createTextOrLink(
					$contactName, $contactUrl ? new Redirect($contactUrl) : null
				)
			)
			->addContentBlock(
				'title',
				ContentBlockFactory::createTitle(
					$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_SELECT')
				)
					->setColor(Text::COLOR_BASE_70)
			)
			->addContentBlock(
				'eventStartDate',
				(new Date())
					->setDate($date)
					->setColor(Text::COLOR_BASE_90)
			)
		;

		return [
			'detail' => $contentBlock,
		];
	}
}