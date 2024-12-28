<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Booking;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Date;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

class BookingCreated extends LogMessage
{
	public function getType(): string
	{
		return 'BookingCreated';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_CREATED_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Icon::INFO;
	}

	public function getContentBlocks(): ?array
	{
		return [
			'bookingCreatedContent' => $this->getBookingCreatedContent(),
		];
	}

	private function getBookingCreatedContent(): ContentBlock
	{
		return (new ContentBlock\ContentBlockWithTitle())
			->setInline()
			->setTitle(Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_SCHEDULED_TIME_TITLE'))
			->setContentBlock(
				(new Date())
					->setDate(
						$this->getBookingScheduledTime()
					)
			)
		;
	}

	private function getBookingScheduledTime(): DateTime
	{
		return DateTime::createFromTimestamp(
			$this->getModel()->getSettings()['datePeriod']['from']['timestamp']
		);
	}
}
