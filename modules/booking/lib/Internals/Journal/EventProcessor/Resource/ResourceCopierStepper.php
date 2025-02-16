<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Journal\EventProcessor\Resource;

use Bitrix\Booking\Internals\Command\Resource\AddCommand;
use Bitrix\Booking\Internals\Command\Resource\AddCommandHandler;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Journal\JournalType;
use Bitrix\Main\Update\Stepper;

class ResourceCopierStepper extends Stepper
{
	public const MODULE = 'booking';

	private const LIMIT = 10;

	function execute(array &$option)
	{
		$outerParams = $this->getOuterParams();
		$eventId = $outerParams[0] ?? 0;
		$totalCopiesCreated = $option['totalCopiesCreated'] ?? 1;
		$event = Container::getJournalRepository()->getById((int)$eventId);

		if (!$event)
		{
			return self::FINISH_EXECUTION;
		}

		if ($event->type !== JournalType::ResourceAdded)
		{
			return self::FINISH_EXECUTION;
		}

		$originalCommand = AddCommand::mapFromArray($event->data);
		$commandWithNoCopies = AddCommand::mapFromArray([...$originalCommand->toArray(), 'copies' => null]);

		for ($i = self::LIMIT; $i >= 0; $i--)
		{
			if ($totalCopiesCreated >= $originalCommand->getCopies())
			{
				return self::FINISH_EXECUTION;
			}

			// add resource
			(new AddCommandHandler())($commandWithNoCopies);

			$totalCopiesCreated++;

			$option['totalCopiesCreated'] = $totalCopiesCreated;
		}

		return self::CONTINUE_EXECUTION;
	}
}
