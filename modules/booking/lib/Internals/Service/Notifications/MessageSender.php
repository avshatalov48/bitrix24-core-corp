<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Notifications;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Message\MessageBodyBased;
use Bitrix\Booking\Entity\Message\MessageTemplateBased;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Model\BookingMessageFailureLogTable;
use Bitrix\Booking\Internals\Model\BookingMessageTable;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class MessageSender
{
	private \Bitrix\Booking\Interfaces\MessageSender $messageSender;

	public function send(Booking $booking, NotificationType $notificationType): Result
	{
		$this->messageSender = MessageSenderPicker::pickByBooking($booking);
		$message = $this->messageSender->createMessage();
		$this->getMessageCreator($message)
			->setBooking($booking)
			->createMessageOfType($notificationType);

		$result = new Result();

		if (!$this->messageSender->canUse())
		{
			$error = ErrorBuilder::build('Sender is not available');
			$this->logError($booking, $notificationType, $error);

			return $result->addError($error);
		}

		$primaryResource = $booking->getPrimaryResource();
		if (!$primaryResource)
		{
			$error = ErrorBuilder::build('Primary resource has not been found');
			$this->logError($booking, $notificationType, $error);

			return $result->addError($error);
		}

		$result = $this->messageSender->send($booking, $message);

		if ($result->isSuccess())
		{
			BookingMessageTable::add([
				'BOOKING_ID' => $booking->getId(),
				'NOTIFICATION_TYPE' => $notificationType->value,
				'SENDER_MODULE_ID' => $this->messageSender->getModuleId(),
				'SENDER_CODE' => $this->messageSender->getCode(),
				// @todo abstract method getMessageId?
				'EXTERNAL_MESSAGE_ID' => (int)$result->getData()['ID'],
			]);
		}
		else
		{
			$error = $result->getError();

			$this->logError($booking, $notificationType, $error);
		}

		return $result;
	}

	private function getMessageCreator(
		MessageTemplateBased|MessageBodyBased $message
	): BookingMessageTemplateBasedCreator|BookingMessageBodyBasedCreator
	{
		if ($message instanceof MessageTemplateBased)
		{
			return new BookingMessageTemplateBasedCreator($message);
		}

		return new BookingMessageBodyBasedCreator($message);
	}

	private function logError(
		Booking $booking,
		NotificationType $notificationType,
		Error $error
	): void
	{
		BookingMessageFailureLogTable::add([
			'BOOKING_ID' => $booking->getId(),
			'NOTIFICATION_TYPE' => $notificationType->value,
			'SENDER_MODULE_ID' => $this->messageSender->getModuleId(),
			'SENDER_CODE' => $this->messageSender->getCode(),
			'REASON_CODE' => $error->getCode() ?? 'DEFAULT',
			'REASON_TEXT' => $error->getMessage(),
		]);
	}
}
