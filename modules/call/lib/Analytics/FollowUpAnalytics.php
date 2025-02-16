<?php

namespace Bitrix\Call\Analytics;

use Bitrix\Call\Analytics\Event\FollowUpEvent;

class FollowUpAnalytics extends AbstractAnalytics
{
	protected const SEND_MESSAGE = 'send_message';

	protected const ANALYTICS_TYPE = [
		'follow_up' => 'follow_up',
		'processing_error' => 'processing_error',
	];

	protected const ANALYTICS_STATUS = [
		'summary' => 'summary',
		'processing_error' => 'processing_error',
	];

	public function addFollowUpResultMessage(): void
	{
		$this->async(function () {
			$this
				->createEvent(self::SEND_MESSAGE)
				?->setType(self::ANALYTICS_TYPE['follow_up'])
				?->setStatus(self::ANALYTICS_STATUS['summary'])
				?->send()
			;
		});
	}

	public function addFollowUpErrorMessage(string $errorCode): void
	{
		$this->async(function () use ($errorCode) {
			$this
				->createEvent(self::SEND_MESSAGE)
				?->setType(self::ANALYTICS_TYPE['processing_error'])
				?->setStatus(self::ANALYTICS_TYPE['processing_error'] . '_' . $errorCode)
				?->send()
			;
		});
	}

	protected function createEvent(
		string $eventName,
	): ?FollowUpEvent
	{
		return (new FollowUpEvent($eventName, $this->call));
	}
}
