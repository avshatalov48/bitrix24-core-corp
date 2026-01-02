<?php

namespace Bitrix\Call\Analytics;

use Bitrix\Call\Analytics\Event\CallEvent;

class CallAnalytics extends AbstractAnalytics
{
	protected const FINISH_CALL = 'finish_call';

	protected const STATUS_SERVER_ENFORCED_TIMEOUT = 'server_enforced_timeout';

	public function finishOldCalls(): void
	{
		$this->async(function () {
			$this
				->createEvent(self::FINISH_CALL)
				?->setStatus(self::STATUS_SERVER_ENFORCED_TIMEOUT)
				?->send()
			;
		});
	}

	protected function createEvent(string $eventName): ?CallEvent
	{
		return (new CallEvent($eventName, $this->call));
	}
}
