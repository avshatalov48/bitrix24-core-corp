<?php

declare(strict_types=1);

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Booking;

use Bitrix\Crm\Dto\Booking\Booking\BookingFields;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Date;
use Bitrix\Main\Type\DateTime;

trait BookingTimeTrait
{
	abstract protected function getBookingFields(): BookingFields;

	private function getBookingCreatedContent(string $title): ContentBlock
	{
		return (new ContentBlock\ContentBlockWithTitle())
			->setInline()
			->setTitle($title)
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
			$this->getBookingFields()->datePeriod->from
		);
	}
}
