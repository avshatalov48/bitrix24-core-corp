<?php

declare(strict_types=1);

namespace Bitrix\Disk\Realtime;

use Bitrix\Disk\Realtime\Channels\Channel;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Realtime\Tags\Tag;
use Bitrix\Main\Loader;
use Bitrix\Pull;
use CPullWatch;

class Event
{
	public function __construct(
		private readonly string $category,
		private readonly array $data = []
	)
	{
	}

	/**
	 * @param array{Pull\Model\Channel|Tag|int} $recipients
	 * @return array
	 */
	final protected function resolveRecipients(array $recipients): array
	{
		$resolved = [];
		foreach ($recipients as $recipient)
		{
			if ($recipient instanceof Channel)
			{
				$resolved[] = $recipient->getPullModel();
			}
			else
			{
				$resolved[] = $recipient;
			}
		}

		return $resolved;
	}

	final public function send(array $recipients): void
	{
		if (!Loader::includeModule('pull'))
		{
			return;
		}

		$recipients = array_filter($recipients);
		$recipients = $this->resolveRecipients($recipients);
		if (empty($recipients))
		{
			return;
		}

		$message = [
			'module_id' => Driver::INTERNAL_MODULE_ID,
			'command' => $this->category,
			'params' => $this->data,
		];

		$toUser = [];
		foreach ($recipients as $recipient)
		{
			if ($recipient instanceof Tag)
			{
				CPullWatch::AddToStack($recipient->getName(), $message);
			}
			else
			{
				$toUser[] = $recipient;
			}
		}

		if ($toUser)
		{
			Pull\Event::add($toUser, $message);
		}
	}
}