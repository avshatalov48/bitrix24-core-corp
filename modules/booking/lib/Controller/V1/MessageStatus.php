<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Controller\V1\Response\MessageStatusGetResponse;
use Bitrix\Booking\Internals\Model\BookingMessageTable;
use Bitrix\Booking\Internals\Notifications\MessageSenderPicker;
use Bitrix\Booking\Internals\NotificationType;
use Bitrix\Booking\Provider\BookingProvider;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Booking\Integration\Booking\Message;

class MessageStatus extends BaseController
{
	private const SEMANTIC_SECONDARY = 'secondary';
	private const SEMANTIC_PRIMARY = 'primary';
	private const SEMANTIC_SUCCESS = 'success';
	private const SEMANTIC_FAILURE = 'failure';

	private BookingProvider $bookingProvider;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->bookingProvider = new BookingProvider();
	}

	public function getAction(int $bookingId): MessageStatusGetResponse|null
	{
		$booking = $this->bookingProvider->getList(
			userId: (int)CurrentUser::get()->getId(),
			filter: ['ID' => $bookingId],
			select: ['CLIENTS'],
		)->getFirstCollectionItem();

		if (!$booking)
		{
			return null;
		}

		if ($booking->getClientCollection()->isEmpty())
		{
			return new MessageStatusGetResponse(
				title: Loc::getMessage('BOOKING_CONTROLLER_MESSAGE_STATUS_SMS_TO_CLIENT'),
				description: Loc::getMessage('BOOKING_CONTROLLER_MESSAGE_STATUS_CLIENT_NOT_SPECIFIED'),
				semantic: self::SEMANTIC_SECONDARY,
				isDisabled: true,
			);
		}

		$lastSentMessage = BookingMessageTable::getList([
			'select' => [
				'NOTIFICATION_TYPE',
				'EXTERNAL_MESSAGE_ID',
				'CREATED_AT',
			],
			'filter' => [
				'=BOOKING_ID' => $bookingId,
			],
			'order' => [
				'CREATED_AT' => 'DESC',
			],
			'limit' => 1,
		])->fetch();

		if (!$lastSentMessage)
		{
			return new MessageStatusGetResponse(
				title: Loc::getMessage('BOOKING_CONTROLLER_MESSAGE_STATUS_SMS_TO_CLIENT'),
				description: Loc::getMessage('BOOKING_CONTROLLER_MESSAGE_STATUS_NOT_SENT'),
				semantic: self::SEMANTIC_SECONDARY,
			);
		}

		$messageStatus = MessageSenderPicker::pickByBooking($booking)->getMessageStatus(
			(int)$lastSentMessage['EXTERNAL_MESSAGE_ID']
		);

		$title = NotificationType::getName($lastSentMessage['NOTIFICATION_TYPE']);;
		$description = $messageStatus->getName();

		/**
		 * Replace description and semantic for confirmation type of message in case it has been already confirmed
		 */
		if (
			$lastSentMessage['NOTIFICATION_TYPE'] === NotificationType::Confirmation->value
			&& $booking->isConfirmed()
		)
		{
			return new MessageStatusGetResponse(
				title: $title,
				description: Loc::getMessage('BOOKING_CONTROLLER_MESSAGE_STATUS_BOOKING_CONFIRMED'),
				semantic: self::SEMANTIC_SUCCESS,
			);
		}

		$semanticsMap = [
			Message\MessageStatus::SEMANTIC_SUCCESS => self::SEMANTIC_PRIMARY,
			Message\MessageStatus::SEMANTIC_FAILURE => self::SEMANTIC_FAILURE,
		];

		return new MessageStatusGetResponse(
			title: $title,
			description: $description,
			semantic: $semanticsMap[$messageStatus->getSemantic()],
		);
	}
}
