<?php

namespace Bitrix\Crm\Integration\VoxImplant;

use Bitrix\Crm\Activity\Provider\Call;
use Bitrix\Crm\Settings\Crm;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\LogMessageController;
use Bitrix\Crm\Timeline\LogMessageEntry;
use Bitrix\Crm\Timeline\LogMessageType;
use Bitrix\Crm\Tracking;
use Bitrix\Main\Event;
use CCrmActivity;
use CCrmOwnerType;
use CVoxImplantMain;

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
		$isIncoming = !isset($data['INCOMING'])
			|| in_array((int)$data['INCOMING'], [CVoxImplantMain::CALL_INCOMING, CVoxImplantMain::CALL_INCOMING_REDIRECT], true);

		if (empty($data) || empty($data['CRM_DATA']) || !$isIncoming)
		{
			return; // nothing event data
		}

		if (isset($data['CALL_ID']))
		{
			$logMessageId = LogMessageEntry::detectIdByParams(
				$data['CALL_ID'],
				LogMessageType::CALL_INCOMING
			);
			if (isset($logMessageId))
			{
				return; // record already created
			}
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

			if (isset($data['CALL_ID']))
			{
				$input['BASE_SOURCE_ID'] = $data['CALL_ID'];
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
	 */
	public static function onCallEnd(array $data): void
	{
		$activityId =(int)$data['CRM_ACTIVITY_ID'];
		if (isset($data['CALL_ID']) && $activityId > 0)
		{
			$logMessageId = LogMessageEntry::detectIdByParams(
				$data['CALL_ID'],
				LogMessageType::CALL_INCOMING
			);
			if (isset($logMessageId))
			{
				TimelineTable::update($logMessageId, [
					'ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
					'ASSOCIATED_ENTITY_ID' => $activityId,
				]);
			}
		}

		$isSuccessfulCall = $data['CALL_DURATION'] > 0
			&& $activityId > 0 							// activity exist
			&& (int)$data['CALL_FAILED_CODE'] !== 304 	// not missed call
		;

		if ($isSuccessfulCall && Crm::isUniversalActivityScenarioEnabled())
		{
			$activityFields = CCrmActivity::GetByID($activityId, false);

			$activityIds = Call::getUncompletedActivityIdList($activityId, Call::UNCOMPLETED_ACTIVITY_INCOMING);
			if (($key = array_search($activityId, $activityIds, true)) !== false) {
				unset($activityIds[$key]); // exclude last call activity ID
			}

			foreach ($activityIds as $activityId)
			{
				CCrmActivity::Complete($activityId, true, ['CUSTOM_CREATION_TIME' => $activityFields['CREATED']]);
			}
		}

		Tracking\Call\EventHandler::onCallEnd($data);
	}
}
