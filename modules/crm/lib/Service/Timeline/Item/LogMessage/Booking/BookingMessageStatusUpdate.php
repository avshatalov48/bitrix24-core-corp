<?php

declare(strict_types=1);

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Booking;

use Bitrix\Booking\Entity;
use Bitrix\Crm\Dto\Booking\Booking\BookingFields;
use Bitrix\Crm\Dto\Booking\Message\Message;
use Bitrix\Crm\Dto\Booking\Message\MessageTypeEnum;
use Bitrix\Crm\Service\Timeline\Item\Activity\Booking\TagMapper;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Client;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class BookingMessageStatusUpdate extends LogMessage
{
	use ClientTrait;

	public function getType(): string
	{
		return 'BookingMessageStatusUpdate';
	}

	public function getTitle(): ?string
	{
		$langMap = [
			MessageTypeEnum::Info->value => Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_MESSAGE_STATUS_UPDATE_TYPE_INFO'),
			MessageTypeEnum::Confirmation->value => Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_MESSAGE_STATUS_UPDATE_TYPE_CONFIRMATION'),
			MessageTypeEnum::Reminder->value => Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_MESSAGE_STATUS_UPDATE_TYPE_REMINDER'),
			MessageTypeEnum::Delayed->value => Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_MESSAGE_STATUS_UPDATE_TYPE_DELAYED'),
			MessageTypeEnum::Feedback->value => Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_MESSAGE_STATUS_UPDATE_TYPE_FEEDBACK'),
		];

		$message = $this->getNotificationMessage();

		return $message ? $langMap[$message->type->value] : '';
	}

	public function getTags(): ?array
	{
		if (
			!Loader::includeModule('booking')
			|| !Loader::includeModule('notifications')
		)
		{
			return null;
		}

		$storedMessage = $this->getModel()->getSettings()['message'] ?? null;
		if (!$storedMessage)
		{
			return null;
		}

		try
		{
			$message = Message::mapFromArray($storedMessage);
		}
		catch (\Throwable)
		{
			return null;
		}

		$tag = TagMapper::mapFromMessage($message);
		if (!$tag)
		{
			return null;
		}

		return [
			'tag' => $tag,
		];
	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$clientBlock = $this->buildClientBlock(
			Client::BLOCK_WITH_FORMATTED_VALUE,
			Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_MESSAGE_STATUS_UPDATE_CLIENT')
		);
		if ($clientBlock)
		{
			$result['clientBlock'] = $clientBlock;
		}

		return $result;
	}

	protected function getPrimaryClient(): ?Entity\Client\Client
	{
		if (!Loader::includeModule('booking'))
		{
			return null;
		}

		return Entity\Client\ClientCollection::mapFromArray(
			array_map(
				static fn (\Bitrix\Crm\Dto\Booking\Client $client) => [
					'id' => $client->id,
					'type' => ['code' => $client->typeCode, 'module' => $client->typeModule],
				],
				$this->getBooking()->clients ?? []
			)
		)->getPrimaryClient();
	}

	protected function getPhoneNumber(): string
	{
		return $this->getModel()->getSettings()['messageInfo']['MESSAGE']['PHONE_NUMBER'] ?? '';
	}

	private function getNotificationMessage(): Message|null
	{
		$message = $this->model->getSettings()['message'] ?? null;
		if (!$message)
		{
			return null;
		}

		try
		{
			return Message::mapFromArray($message);
		}
		catch (\Throwable)
		{
			return null;
		}
	}

	private function getBooking(): BookingFields|null
	{
		return BookingFields::tryFrom($this->getModel()->getSettings()['booking'] ?? []);
	}
}
