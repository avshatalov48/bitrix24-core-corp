<?php

namespace Bitrix\Sign\Item\Integration\Im\Notification;

use Bitrix\Sign\Type\Im\Notification\NotificationType;

final class Message
{
	public function __construct(
		public int $fromUserId,
		public int $toUserId,
		public NotificationType $type,
		public ?string $title,
		/** @var \Closure(): string | string $message */
		public \Closure|string $message,
	)
	{
	}
}