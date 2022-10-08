<?php

namespace Bitrix\Crm\Integration\VoxImplant;

use Bitrix\Crm\Timeline\LogMessageController;
use Bitrix\Crm\Timeline\LogMessageType;
use Bitrix\Crm\Tracking;
use Bitrix\Main\Event;
use CCrmOwnerType;

/**
 * Class EventHandler of VoxImplant events
 *
 * @package Bitrix\Crm\Integration\VoxImplant
 */
class EventHandler
{
	/**
	 * Event handler when CRM entities created during a call.
	 *
	 * @param Event $event
	 */
	public static function onCallRegisteredInCrm(Event $event): void
	{
		$data = $event->getParameters();
		if (empty($data) || empty($data['CRM_DATA']))
		{
			return;
		}

		$createdEntities = array_values(
			array_filter(
				$data['CRM_DATA'],
				static fn($row) => in_array((int)$row['OWNER_TYPE_ID'], [CCrmOwnerType::Lead, CCrmOwnerType::Deal], true)
			)
		);
		$baseEntities = array_values(
			array_filter(
				$data['CRM_DATA'],
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

			if (isset($data['CALLER_ID']))
			{
				$input['BASE_SOURCE'] = $data['CALLER_ID'];
			}
		}

		LogMessageController::getInstance()->onCreate(
			$input,
			LogMessageType::CALL_INCOMING,
			$data['USER_ID'] ?? null
		);
	}

	/**
	 * Handler of call end event.
	 *
	 * @param array $data Event data.
	 * @return void
	 */
	public static function onCallEnd($data)
	{
		Tracking\Call\EventHandler::onCallEnd($data);
	}
}
