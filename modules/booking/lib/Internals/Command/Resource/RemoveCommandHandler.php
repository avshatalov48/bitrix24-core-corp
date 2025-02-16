<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Resource;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Exception\Resource\RemoveResourceException;
use Bitrix\Booking\Internals\Journal\JournalEvent;
use Bitrix\Booking\Internals\Journal\JournalType;
use Bitrix\Booking\Internals\Query\Booking\GetListFilter;

class RemoveCommandHandler
{
	public function __invoke(RemoveCommand $command): void
	{
		$hasResources = Container::getBookingRepository()->getList(
			limit: 1,
			filter: new GetListFilter([
				'RESOURCE_ID' => [$command->id],
			])
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
