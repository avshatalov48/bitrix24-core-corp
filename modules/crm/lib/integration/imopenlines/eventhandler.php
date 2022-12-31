<?php

namespace Bitrix\Crm\Integration\ImOpenLines;

use Bitrix\Crm\Timeline\LogMessageController;
use Bitrix\Crm\Timeline\LogMessageType;
use Bitrix\Main\Event;
use CCrmOwnerType;

class EventHandler
{
	/**
	 * Event handler when CRM entities created in open lines.
	 *
	 * @param Event $event
	 */
	public static function OnImOpenLineRegisteredInCrm(Event $event): void
	{
		$data = $event->getParameters();
		if (empty($data) || empty($data['BINDINGS']))
		{
			return;
		}

		$createdEntities = array_values(
			array_filter(
				$data['BINDINGS'],
				static fn($row) => in_array((int)$row['OWNER_TYPE_ID'], [CCrmOwnerType::Lead, CCrmOwnerType::Deal], true)
			)
		);
		$baseEntities = array_values(
			array_filter(
				$data['BINDINGS'],
				static fn($row) => in_array((int)$row['OWNER_TYPE_ID'], [CCrmOwnerType::Contact, CCrmOwnerType::Company], true)
			)
		);

		if (empty($createdEntities))
		{
			return;
		}

		$input = [
			"ENTITY_TYPE_ID" => $createdEntities[0]['OWNER_TYPE_ID'],
			"ENTITY_ID" => $createdEntities[0]['OWNER_ID'],
		];

		if (!empty($baseEntities))
		{
			$input['BASE_ENTITY_TYPE_ID'] = $baseEntities[0]['OWNER_TYPE_ID'];
			$input['BASE_ENTITY_ID'] = $baseEntities[0]['OWNER_ID'];

			if (isset($data['PROVIDER_PARAMS']['USER_CODE']))
			{
				$input['BASE_SOURCE'] = $data['PROVIDER_PARAMS']['USER_CODE'];
			}
		}

		LogMessageController::getInstance()->onCreate(
			$input,
			LogMessageType::OPEN_LINE_INCOMING,
			$data['AUTHOR_ID'] ?? null
		);
	}
}
