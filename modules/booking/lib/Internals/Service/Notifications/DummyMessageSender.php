<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Notifications;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Message\MessageBodyBased;
use Bitrix\Booking\Entity\Message\MessageStatus;
use Bitrix\Booking\Interfaces\MessageSender;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Event;
use Bitrix\Main\Result;

class DummyMessageSender implements MessageSender
{
	public function getModuleId(): string
	{
		return 'booking';
	}

	public function getCode(): string
	{
		return 'dummy';
	}

	public function createMessage(): MessageBodyBased
	{
		return new MessageBodyBased();
	}

	public function send(Booking $booking, $message): Result
	{
		if (!$message instanceof MessageBodyBased)
		{
			throw new ArgumentException('Message should be instance of MessageBodyBased');
		}

		(new Event(
			'booking',
			'onDummyMessageSenderSendMessage',
			[
				'MESSAGE_BODY' => $message->getMessageBody(),
			]
		))->send();

		return new Result();
	}

	public function getMessageStatus(int $messageId): MessageStatus
	{
		return MessageStatus::delivered();
	}

	public function canUse(): bool
	{
		return true;
	}
}
