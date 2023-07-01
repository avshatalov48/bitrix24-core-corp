<?php

namespace Bitrix\Crm\Integration\ImOpenLines;

use Bitrix\Crm\Activity\Provider\ProviderManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\ActivityController;
use Bitrix\Crm\Timeline\LogMessageController;
use Bitrix\Crm\Timeline\LogMessageType;
use Bitrix\ImOpenLines\Session;
use Bitrix\Main\Event;
use CCrmActivity;
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

	/**
	 * Event handler when close chat.
	 *
	 * @param Event $event
	 */
	public static function OnChatFinish(Event $event): void
	{
		$parameters = $event->getParameters();
		$session = $parameters['RUNTIME_SESSION'];
		if (!$session instanceof Session)
		{
			return;
		}

		$activityId = (int)($session->getData('CRM_ACTIVITY_ID') ?? 0);
		if ($activityId <= 0)
		{
			return;
		}

		$activity = CCrmActivity::GetByID($activityId);
		if (!$activity)
		{
			return;
		}

		if (isset($activity['COMPLETED']) && $activity['COMPLETED'] === 'Y')
		{
			return;
		}

		$bindings = CCrmActivity::GetBindings($activityId);
		if (!$bindings)
		{
			return;
		}

		$userPermissions = Container::getInstance()->getUserPermissions()->getCrmPermissions();
		$isAtLeastOnePermissionEnabled = false;
		foreach ($bindings as $binding)
		{
			if (
				CCrmActivity::CheckCompletePermission(
					$binding['OWNER_TYPE_ID'],
					$binding['OWNER_ID'],
					$userPermissions,
					['FIELDS' => $activity]
				)
			)
			{
				$isAtLeastOnePermissionEnabled = true;

				break;
			}
		}

		if ($isAtLeastOnePermissionEnabled)
		{
			CCrmActivity::Complete(
				$activityId,
				true,
				[
					'REGISTER_SONET_EVENT' => true,
					'SKIP_BEFORE_HANDLER' => true,
				]
			);
		}
	}

	/**
	 * Event handler when Transfer dialog to other roperator.
	 *
	 * @param Event $event
	 */
	public static function OnOperatorTransfer(Event $event): void
	{
		$activity = [];

		$parameters = $event->getParameters();
		$session = $parameters['SESSION'];
		if (!$session instanceof Session)
		{
			return;
		}
		
		$activityId = (int)($session->getData('CRM_ACTIVITY_ID') ?? 0);
		if ($activityId > 0)
		{
			$activity = CCrmActivity::GetByID($activityId, false);
		}

		$activity = is_array($activity) ? $activity : [];
		if (!empty($activity))
		{
			ActivityController::getInstance()
				->notifyTimelinesAboutActivityUpdate($activity, (int)$activity['RESPONSIBLE_ID'])
			;

			ProviderManager::syncBadgesOnActivityUpdate((int)$activity['ID'], $activity);
		}
	}
}
