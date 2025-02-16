<?php

declare(strict_types=1);

namespace Bitrix\Booking\Integration\Booking\Message;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Booking\ClientCollection;
use Bitrix\Booking\Internals\Model\BookingMessageFailureLogTable;
use Bitrix\Booking\Internals\Model\BookingMessageTable;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

abstract class MessageSender
{
	abstract public function getModuleId(): string;

	abstract public function getCode(): string;

	/**
	 * @return class-string<MessageTemplateBased|MessageBodyBased>
	 */
	abstract function getMessageClass(): string;

	/**
	 * @param ClientCollection $clientCollection
	 * @param Message $message
	 * @return Result
	 */
	public function sendMessage(Booking $booking, $message): Result
	{
		$result = new Result();

		if (!$this->canUse())
		{
			$error = new Error('Sender is not available');
			$this->logError($booking, $message, $error);

			return $result->addError($error);
		}

		$primaryResource = $booking->getPrimaryResource();
		if (!$primaryResource)
		{
			$error = new Error('Primary resource has not been found');
			$this->logError($booking, $message, $error);

			return $result->addError($error);
		}

		$result = $this->sendMessageConcrete($booking, $message);

		if ($result->isSuccess())
		{
			BookingMessageTable::add([
				'BOOKING_ID' => $booking->getId(),
				'NOTIFICATION_TYPE' => $message->getNotificationType()->value,
				'SENDER_MODULE_ID' => $this->getModuleId(),
				'SENDER_CODE' => $this->getCode(),
				// @todo abstract method getMessageId?
				'EXTERNAL_MESSAGE_ID' => (int)$result->getData()['ID'],
			]);
		}
		else
		{
			$error = $result->getError();

			$this->logError($booking, $message, $error);
		}

		return $result;
	}

	private function logError(Booking $booking, $message, Error $error): void
	{
		BookingMessageFailureLogTable::add([
			'BOOKING_ID' => $booking->getId(),
			'NOTIFICATION_TYPE' => $message->getNotificationType()->value,
			'SENDER_MODULE_ID' => $this->getModuleId(),
			'SENDER_CODE' => $this->getCode(),
			'REASON_CODE' => $error->getCode() ?? 'DEFAULT',
			'REASON_TEXT' => $error->getMessage(),
		]);
	}

	abstract public function getMessageStatus(int $messageId): MessageStatus;

	abstract protected function sendMessageConcrete(Booking $booking, $message): Result;

	abstract public function canUse(): bool;
}
