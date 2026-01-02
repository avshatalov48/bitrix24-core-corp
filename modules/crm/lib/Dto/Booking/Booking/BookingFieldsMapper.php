<?php

declare(strict_types=1);

namespace Bitrix\Crm\Dto\Booking\Booking;

class BookingFieldsMapper
{
	public static function mapFromBookingArray(array $booking, bool $isOverbooking = false): BookingFields
	{
		return BookingFields::mapFromArray([
			'id' => $booking['id'],
			'datePeriod' => [
				'from' => $booking['datePeriod']['from']['timestamp'],
				'fromTimezone' => $booking['datePeriod']['from']['timezone'],
				'to' => $booking['datePeriod']['to']['timestamp'],
				'toTimezone' => $booking['datePeriod']['to']['timezone'],
			],
			'isOverbooking' => $isOverbooking,
			'isConfirmed' => $booking['isConfirmed'] ?? false,
			'resources' => array_map(static fn (array $resource) => [
				'typeName' => $resource['type']['name'],
				'name' => $resource['name'],
			], $booking['resources'] ?? []),
			'clients' => array_map(static fn (array $client) => [
				'typeModule' => $client['type']['module'],
				'typeCode' => $client['type']['code'],
				'id' => $client['id'],
				'phones' => $client['data']['phones'] ?? [],
			], $booking['clients'] ?? []),
			'externalData' => array_map(static fn (array $externalData) => [
				'moduleId' => $externalData['moduleId'],
				'entityTypeId' => $externalData['entityTypeId'],
				'value' => $externalData['value'],
			], $booking['externalData'] ?? []),
			'name' => $booking['name'] ?? null,
			'createdBy' => $booking['createdBy'] ?? null,
			'note' => $booking['note'] ?? null,
		]);
	}
}
