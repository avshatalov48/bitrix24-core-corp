<?php

declare(strict_types=1);

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Booking;

use Bitrix\Booking\Entity;
use Bitrix\Crm\Dto\Booking\Booking\BookingFields;
use Bitrix\Crm\Dto\Booking\Booking\BookingFieldsMapper;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Client;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class BookingCreated extends LogMessage
{
	use ClientTrait;
	use BookingTimeTrait;

	public function getType(): string
	{
		return 'BookingCreated';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_CREATED_TITLE');
	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$clientBlock = $this->buildClientBlock(
			Client::BLOCK_WITH_FORMATTED_VALUE,
			Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_CREATED_CLIENT')
		);
		if ($clientBlock)
		{
			$result['clientBlock'] = $clientBlock;
		}

		$result['bookingCreatedContent'] = $this->getBookingCreatedContent(
			Loc::getMessage('CRM_TIMELINE_LOG_BOOKING_SCHEDULED_TIME_TITLE_MSGVER_1') ?? ''
		);

		return $result;
	}

	protected function getBookingFields(): BookingFields
	{
		$fields = $this->getModel()->getSettings();
		$booking = $fields['booking'] ?? $fields;

		return isset($booking['description'])
			// bc for old format
			? BookingFieldsMapper::mapFromBookingArray($booking)
			: BookingFields::mapFromArray($booking)
		;
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
}
