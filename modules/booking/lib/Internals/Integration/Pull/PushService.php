<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Pull;

use Bitrix\Main;
use Bitrix\Pull\Event;

class PushService
{
	public function sendEvent(PushEvent $event): void
	{
		if (!Main\Loader::includeModule('pull'))
		{
			return;
		}

		($event->isTag())
			? \CPullWatch::AddToStack($event->getTag(), $event->getParams())
			: Event::add($event->getRecipients(), $event->getParams())
		;
	}

	public function subscribeByTag(string $tag, int $userId): void
	{
		if (!Main\Loader::includeModule('pull'))
		{
			return;
		}

		\CPullWatch::Add(
			userId: $userId,
			tag: $tag,
			immediate: true
		);
	}
}
