<?php

declare(strict_types=1);

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Booking;

use Bitrix\Booking\Entity;
use Bitrix\Crm\Dto\Booking\Booking\BookingFields;
use Bitrix\Crm\Dto\Booking\Booking\BookingStatusEnum;
use Bitrix\Crm\Dto\Booking\Message\Message;
use Bitrix\Crm\Service\Timeline\Item\Activity\Booking\TagMapper;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Client;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class BookingStatusUpdate extends LogMessage
{
	use ClientTrait;
	use BookingTimeTrait;

	public function getType(): string
	{
		return 'BookingStatusUpdate';
	}

	public function getTitle(): ?string
	{
		$status = $this->getStatus();
		if (!$status)
		{
			Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_STATUS_UPDATE_TITLE');
		}

		return match ($status)
		{
			BookingStatusEnum::ComingSoon => Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_STATUS_UPDATE_TITLE_COMING_SOON'),
			default => Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_STATUS_UPDATE_TITLE'),
		};
	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$clientBlock = $this->buildClientBlock(
			Client::BLOCK_WITH_FORMATTED_VALUE,
			Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_STATUS_UPDATE_CLIENT')
		);
		if ($clientBlock)
		{
			$result['clientBlock'] = $clientBlock;
		}

		$result['bookingCreatedContent'] = $this->getBookingCreatedContent(
			Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_STATUS_UPDATE_SCHEDULED_TIME_TITLE') ?? ''
		);

		return $result;
	}

	public function getTags(): ?array
	{
		$message = $this->getNotificationMessage();
		$status = $this->getStatus();
		if (!$message && !$status)
		{
			return null;
		}

		$tag = TagMapper::mapFromMessageAndStatus($message, $status);
		if (!$tag)
		{
			return null;
		}

		return [
			'tag' => $tag,
		];
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
				$this->getBookingFields()->clients ?? []
			)
		)->getPrimaryClient();
	}

	protected function getPhoneNumber(): string
	{
		return $this->getBookingFields()->clients[0]->phones[0] ?? '';
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

	private function getStatus(): BookingStatusEnum|null
	{
		$storedStatus = $this->model->getSettings()['status'] ?? null;

		return $storedStatus && is_string($storedStatus) ? BookingStatusEnum::tryFrom($storedStatus) : null;
	}

	protected function getBookingFields(): BookingFields
	{
		return BookingFields::tryFrom($this->getModel()->getSettings()['booking'] ?? []);
	}
}
