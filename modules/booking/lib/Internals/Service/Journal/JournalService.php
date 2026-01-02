<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Journal;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\Journal\EventProcessor\Counter\CounterEventProcessor;
use Bitrix\Booking\Internals\Service\Journal\EventProcessor\PushPull\PushPullEventProcessor;
use Bitrix\Booking\Internals\Service\Journal\EventProcessor\Booking\BookingEventProcessor;
use Bitrix\Booking\Internals\Service\Journal\EventProcessor\Resource\ResourceEventProcessor;
use Bitrix\Booking\Internals\Service\Journal\EventProcessor\ResourceType\ResourceTypeEventProcessor;
use Bitrix\Booking\Internals\Service\Journal\EventProcessor\WaitListItem\WaitListItemEventProcessor;
use Bitrix\Main\Application;

final class JournalService implements JournalServiceInterface
{
	private const LOCK_KEY = 'booking.journallock';

	public function __construct()
	{
		$this->enableJob();
	}

	public function append(JournalEvent $event): void
	{
		Container::getJournalRepository()->append($event);
	}

	private function enableJob(): void
	{
		$application = Application::getInstance();
		$application && $application->addBackgroundJob(
			['\Bitrix\Booking\Internals\Service\Journal\JournalService', 'process'],
			[],
			Application::JOB_PRIORITY_LOW - 2
		);
	}

	public static function process(): void
	{
		if (!Application::getConnection()->lock(self::LOCK_KEY))
		{
			return;
		}

		$eventCollection = Container::getJournalRepository()->getPending();
		if ($eventCollection->isEmpty())
		{
			return;
		}

		(new BookingEventProcessor())->process($eventCollection);
		(new ResourceEventProcessor(Container::getDelayedTaskService()))->process($eventCollection);
		(new ResourceTypeEventProcessor())->process($eventCollection);
		(new WaitListItemEventProcessor())->process($eventCollection);
		(new CounterEventProcessor())->process($eventCollection);
		(new PushPullEventProcessor())->process($eventCollection);
		// other event processors ...

		Container::getJournalRepository()->markProcessed($eventCollection);

		Application::getConnection()->unlock(self::LOCK_KEY);
	}
}
