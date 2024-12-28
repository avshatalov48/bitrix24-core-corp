<?php

namespace Bitrix\Sign\Service\Analytic;

use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\Event;
use Bitrix\Sign\Item;

class AnalyticService
{
	public function sendEventWithSigningContext(
		AnalyticsEvent $event,
		?Item\Member $member = null,
	): void
	{
		$event->send();

		$onAnalyticSendEvent = $this->createOnAnalyticSendSignEvent($event, $member);
		try
		{
			$onAnalyticSendEvent->send();
		}
		catch (\Throwable $throwable)
		{
		}
	}

	private function createOnAnalyticSendSignEvent(AnalyticsEvent $event, ?Item\Member $member): Event
	{
		return new Event(
			'sign',
			'OnAnalyticsEvent',
			[
				'analyticsEvent' => $event,
				'eventData' => $event->exportToArray(),
				'member' => $member,
			],
		);
	}
}