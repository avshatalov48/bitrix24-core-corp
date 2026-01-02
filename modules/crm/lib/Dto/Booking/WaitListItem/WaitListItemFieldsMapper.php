<?php

declare(strict_types=1);

namespace Bitrix\Crm\Dto\Booking\WaitListItem;

class WaitListItemFieldsMapper
{
	public static function mapFromWaitListItemArray(array $waitListItem): WaitListItemFields
	{
		return WaitListItemFields::mapFromArray([
			'id' => $waitListItem['id'],
			'clients' => array_map(static fn (array $client) => [
				'typeModule' => $client['type']['module'],
				'typeCode' => $client['type']['code'],
				'id' => $client['id'],
			], $waitListItem['clients'] ?? []),
			'externalData' => array_map(static fn (array $resource) => [
				'moduleId' => $resource['moduleId'],
				'entityTypeId' => $resource['entityTypeId'],
				'value' => $resource['value'],
			], $waitListItem['externalData'] ?? []),
			'createdBy' => $waitListItem['createdBy'] ?? null,
			'note' => $waitListItem['note'] ?? null,
		]);
	}
}
