<?php

declare(strict_types=1);

namespace Bitrix\Crm\Activity\Provider\Booking;

use Bitrix\Crm;
use Bitrix\Crm\Activity\Entity;
use Bitrix\Crm\Activity\Provider\ToDo;
use Bitrix\Crm\Dto\Booking\Booking\BookingFields;
use Bitrix\Crm\Dto\Booking\Booking\BookingStatusEnum;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

class BookingToDo
{
	public function createForBooking(BookingFields $booking, BookingStatusEnum $status): void
	{
		$bindings = BookingCommon::makeBindings($booking);

		if (empty($bindings))
		{
			return;
		}

		$descriptionPhrase = match ($status)
		{
			BookingStatusEnum::DelayedCounterActivated => 'CRM_ACTIVITY_PROVIDER_BOOKING_TODO_DESCRIPTION_DELAY',
			BookingStatusEnum::ConfirmCounterActivated => 'CRM_ACTIVITY_PROVIDER_BOOKING_TODO_DESCRIPTION_CONFIRM',
			default => null,
		};

		if (!$descriptionPhrase)
		{
			return;
		}

		$description = Loc::getMessage($descriptionPhrase);

		foreach ($bindings as $binding)
		{
			$identifier = new Crm\ItemIdentifier($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']);
			$todo = new Entity\ToDo(
				$identifier,
				new ToDo\ToDo(),
			);

			$todo
				->setDeadline(new DateTime())
				->setDefaultSubject()
			;

			if ($description)
			{
				$todo->setDescription($description);
			}

			$todo->save();
		}
	}
}
