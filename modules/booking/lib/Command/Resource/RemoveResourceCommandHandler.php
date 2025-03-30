<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Resource;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\Resource\RemoveResourceException;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;

class RemoveResourceCommandHandler
{
	public function __invoke(RemoveResourceCommand $command): void
	{
		$hasResources = Container::getBookingRepository()->getList(
			limit: 1,
			filter: new BookingFilter(['RESOURCE_ID' => [$command->id]]),
		)->isEmpty();

		if (!$hasResources)
		{
			throw new RemoveResourceException('The resource can not be deleted. There are bookings with that resource.');
		}

		Container::getTransactionHandler()->handle(
			fn: function() use ($command) {
				// remove resource
				Container::getResourceRepository()->remove($command->id);
				// append command to the journal
				Container::getJournalService()->append(
					new JournalEvent(
						entityId: $command->id,
						type: JournalType::ResourceDeleted,
						data: $command->toArray(),
					),
				);
			},
			errType: RemoveResourceException::class,
		);
	}
}
