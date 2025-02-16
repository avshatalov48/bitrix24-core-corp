<?php

declare(strict_types=1);

namespace Bitrix\Booking\Integration\Booking\Message;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Exception\InvalidArgumentException;
use Bitrix\Booking\Internals\NotificationType;
use Bitrix\Main\Result;

abstract class Message
{
	private MessageSender $sender;
	private NotificationType $notificationType;

	public function __construct(MessageSender $sender, NotificationType $notificationType)
	{
		$this->sender = $sender;
		$this->notificationType = $notificationType;
	}

	public function getNotificationType(): NotificationType
	{
		return $this->notificationType;
	}

	public function send(Booking $booking): Result
	{
		if ($booking->isNew())
		{
			throw new InvalidArgumentException('Existing booking expected');
		}

		return $this->sender->sendMessage($booking, $this);
	}
}
