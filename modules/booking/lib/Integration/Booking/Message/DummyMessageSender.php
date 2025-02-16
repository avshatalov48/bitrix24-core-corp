<?php

declare(strict_types=1);

namespace Bitrix\Booking\Integration\Booking\Message;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Event;
use Bitrix\Main\Result;

class DummyMessageSender extends MessageSender
{
	public function getModuleId(): string
	{
		return 'booking';
	}

	public function getCode(): string
	{
		return 'dummy';
	}

	/**
	 * @inheritdoc
	 */
	public function getMessageClass(): string
	{
		return MessageBodyBased::class;
	}

	protected function sendMessageConcrete(Booking $booking, $message): Result
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
