<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity\CommunicationStatistics;
use Bitrix\Crm\Activity\IncomingChannel;
use Bitrix\Crm\Badge;
use Bitrix\Crm\Communication;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\Integration\VoxImplantManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\ActivitySettings;
use Bitrix\Crm\Timeline\LogMessageType;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant\ConfigTable;
use CCrmActivity;
use CCrmActivityType;

Loc::loadMessages(__FILE__);

class Call extends Base
{
	public const ACTIVITY_PROVIDER_ID = 'VOXIMPLANT_CALL';
	public const ACTIVITY_PROVIDER_TYPE_CALL = 'CALL';
	public const ACTIVITY_PROVIDER_TYPE_CALLBACK = 'CALLBACK';

	public const UNCOMPLETED_ACTIVITY_MISSED = 1;
	public const UNCOMPLETED_ACTIVITY_INCOMING = 2;

	public static function getId()
	{
		return static::ACTIVITY_PROVIDER_ID;
	}

	public static function isActive()
	{
		$result = false;
		if (Loader::includeModule('voximplant'))
		{
			$config = ConfigTable::getList(array(
				'select' => array('ID'),
				'limit' => 1
			))->fetch();

			$result = ($config !== false);
		}
		return $result;
	}

	public static function getStatusAnchor()
	{
		if (!Loader::includeModule('voximplant'))
		{
			return parent::getStatusAnchor();
		}

		return array(
			'TEXT' => (static::isActive() ? Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_ACTIVE') : Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_INACTIVE')),
			'URL' => \CVoxImplantMain::GetPublicFolder().'lines.php'
		);
	}

	public static function getTypeId(array $activity)
	{
		if (!empty($activity['PROVIDER_TYPE_ID']))
		{
			return $activity['PROVIDER_TYPE_ID'];
		}

		return static::ACTIVITY_PROVIDER_TYPE_CALL;
	}

	public static function getName()
	{
		return Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALL_NAME');
	}

	/**
	 * Returns supported provider's types
	 * @return array
	 */
	public static function getTypes()
	{
		return array(
			array(
				'NAME' => Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALL_NAME'),
				'PROVIDER_ID' => static::ACTIVITY_PROVIDER_ID,
				'PROVIDER_TYPE_ID' => static::ACTIVITY_PROVIDER_TYPE_CALL,
				'DIRECTIONS' => array(
					\CCrmActivityDirection::Incoming => Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALL_INCOMING'),
					\CCrmActivityDirection::Outgoing => Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALL_OUTGOING'),
				),
			),
			array(
				'NAME' => Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALLBACK_NAME'),
				'PROVIDER_ID' => static::ACTIVITY_PROVIDER_ID,
				'PROVIDER_TYPE_ID' => static::ACTIVITY_PROVIDER_TYPE_CALLBACK,
				'DIRECTIONS' => array(
					\CCrmActivityDirection::Outgoing => Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALLBACK_OUTGOING'),
				),
			)
		);
	}

	public static function getTypesFilterPresets()
	{
		// Call presets is already in filter (compatible TYPE_ID = CCrmActivityType::Call)
		// Add Callback only.
		return array(
			array(
				'NAME' => Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALLBACK_OUTGOING'),
				'PROVIDER_TYPE_ID' => static::ACTIVITY_PROVIDER_TYPE_CALLBACK,
				'DIRECTION' => \CCrmActivityDirection::Outgoing
			)
		);
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @param int $direction Activity direction.
	 * @return bool
	 */
	public static function getTypeName($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		if (!$providerTypeId || $providerTypeId === static::ACTIVITY_PROVIDER_TYPE_CALL)
		{
			return $direction == \CCrmActivityDirection::Incoming?
				Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALL_INCOMING')
				:  Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALL_OUTGOING');
		}
		return parent::getTypeName($providerTypeId, $direction);
	}

	/**
	 * @param string $action Action ADD or UPDATE.
	 * @param array $fields Activity fields.
	 * @param int $id Activity ID.
	 * @param null|array $params Additional parameters.
	 * @return Main\Result Check fields result.
	 */
	public static function checkFields($action, &$fields, $id, $params = null)
	{
		$result = new Main\Result();

		if (empty($fields['PROVIDER_TYPE_ID']))
		{
			$fields['PROVIDER_TYPE_ID'] = static::ACTIVITY_PROVIDER_TYPE_CALL;
		}

		//Only START_TIME can be taken for DEADLINE!
		if ($action === self::ACTION_UPDATE)
		{
			if (isset($fields['START_TIME']) && $fields['START_TIME'] !== '')
			{
				$fields['DEADLINE'] = $fields['START_TIME'];
			}
			elseif (isset($fields['~START_TIME']) && $fields['~START_TIME'] !== '')
			{
				$fields['~DEADLINE'] = $fields['~START_TIME'];
			}
		}

		return $result;
	}

	public static function canUseCalendarEvents($providerTypeId = null): bool
	{
		return $providerTypeId === static::ACTIVITY_PROVIDER_TYPE_CALL;
	}

	public static function canAddCalendarEvents(?string $providerTypeId = null): bool
	{
		if (static::canUseCalendarEvents($providerTypeId))
		{
			return ActivitySettings::getValue(ActivitySettings::ENABLE_CREATE_CALENDAR_EVENT_FOR_CALL);
		}

		return false;
	}

	public static function canKeepCompletedInCalendar($providerTypeId = null)
	{
		return ActivitySettings::getValue(ActivitySettings::KEEP_COMPLETED_CALLS);
	}

	public static function canKeepReassignedInCalendar($providerTypeId = null)
	{
		return ActivitySettings::getValue(ActivitySettings::KEEP_REASSIGNED_CALLS);
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @return bool
	 */
	public static function canUseLiveFeedEvents($providerTypeId = null)
	{
		return true;
	}

	/**
	 * @param array $activity Activity data.
	 * @return bool
	 */
	public static function checkForWaitingCompletion(array $activity)
	{
		return !(isset($activity['COMPLETED']) && $activity['COMPLETED'] === 'Y')
			|| isset($activity['DIRECTION']) && $activity['DIRECTION'] == \CCrmActivityDirection::Incoming;
	}

	public static function getCommunicationType($providerTypeId = null)
	{
		return static::COMMUNICATION_TYPE_UNDEFINED;
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @param int $direction Activity direction.
	 * @return bool
	 */
	public static function isTypeEditable($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		if ($providerTypeId === static::ACTIVITY_PROVIDER_TYPE_CALL)
		{
			return true;
		}

		return false;
	}

	/**
	 * @param array $params Activity params.
	 * @return array Actions list.
	 */
	public static function getPlannerActions(array $params = null)
	{
		if (!ActivitySettings::areOutdatedCalendarActivitiesEnabled())
		{
			return [];
		}

		return array(
			array(
				'ACTION_ID' => self::getId().'_'.self::ACTIVITY_PROVIDER_TYPE_CALL,
				'NAME' => Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALL_PLANNER_ACTION_NAME'),
				'TYPE_ID' => CCrmActivityType::Call,
				'PROVIDER_ID' => self::getId(),
				'PROVIDER_TYPE_ID' => self::ACTIVITY_PROVIDER_TYPE_CALL
			)
		);
	}

	/**
	 * @param array $activity Activity data.
	 * @return string Title.
	 */
	public static function getPlannerTitle(array $activity)
	{
		return Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALL_PLANNER_ACTION_NAME');
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @param int $direction Activity direction.
	 * @param array|null $replace Message replace templates.
	 * @return string
	 */
	public static function generateSubject($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined, array $replace = null)
	{
		if ($direction === \CCrmActivityDirection::Incoming)
		{
			return Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALL_INCOMING_SUBJECT', $replace);
		}

		if ($direction === \CCrmActivityDirection::Outgoing)
		{
			return Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALL_OUTGOING_SUBJECT', $replace);
		}

		return parent::generateSubject($providerTypeId, $direction, $replace);
	}

	/**
	 * @param array $activity Activity data.
	 * @return array Fields.
	 */
	public static function getFieldsForEdit(array $activity)
	{
		$parentFields = parent::getFieldsForEdit($activity);
		$fields = array(
			array(
				'LABEL' => Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALL_PLANNER_SUBJECT_LABEL'),
				'TYPE' => 'SUBJECT',
				'VALUE' => $activity['SUBJECT'] ?? ''
			)
		);

		$callId = mb_strpos($activity['ORIGIN_ID'] ?? '', 'VI_') === false ? null : mb_substr($activity['ORIGIN_ID'], 3);
		$callInfo = VoxImplantManager::getCallInfo($callId);
		if ($callInfo)
		{
			$fields[] = array(
				'LABEL' => Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALL_COMMENT'),
				'TYPE' => 'TEXT',
				'NAME' => 'COMMENT',
				'VALUE' => $callInfo['COMMENT'] ?? ''
			);
		}

		return array_merge($fields, $parentFields);
	}

	/**
	 * @param array $activity
	 */
	public static function fillDefaultActivityFields(array &$activity)
	{
		$activity['NOTIFY_TYPE'] = \CCrmActivityNotifyType::Min;
		$activity['NOTIFY_VALUE'] = 15;
		$activity['DIRECTION'] = \CCrmActivityDirection::Outgoing;

		if (empty($activity['PROVIDER_TYPE_ID']))
		{
			$activity['PROVIDER_TYPE_ID'] = static::ACTIVITY_PROVIDER_TYPE_CALL;
		}
	}

	public static function postForm(array &$activity, array $formData)
	{
		$result = new Main\Result();
		if ($formData['comment'] ?? null)
		{
			$activityId = $formData['id'];
			$activityFields = CCrmActivity::GetByID($activityId, false);

			$callId = mb_strpos($activityFields['ORIGIN_ID'], 'VI_') === false? null : mb_substr($activityFields['ORIGIN_ID'], 3);
			if ($callId)
			{
				VoxImplantManager::saveComment($callId, $formData['comment']);
			}
		}

		if (is_array($activity['COMMUNICATIONS']))
		{
			foreach ($activity['COMMUNICATIONS'] as $k => $v)
			{
				if ($activity['COMMUNICATIONS'][$k]['TYPE'] == '' && $activity['COMMUNICATIONS'][$k]['VALUE'] == '')
				{
					$firstNumber = static::getFirstPhoneNumber($activity['COMMUNICATIONS'][$k]['ENTITY_TYPE_ID'], $activity['COMMUNICATIONS'][$k]['ENTITY_ID']);
					if ($firstNumber === '')
					{
						$result->addError(new Main\Error(Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALL_ERROR_NO_NUMBER')));
						return $result;
					}

					$activity['COMMUNICATIONS'][$k]['VALUE'] = $firstNumber;
					$activity['COMMUNICATIONS'][$k]['TYPE'] = static::COMMUNICATION_TYPE_PHONE;
				}
			}
		}

		return $result;
	}

	public static function getFirstPhoneNumber($entityTypeId, $entityId)
	{
		$phones = Communication\Manager::resolveEntityCommunicationData($entityTypeId, $entityId, [Communication\Type::PHONE]);

		if (is_array($phones) && count($phones) > 0)
		{
			return $phones[0]['VALUE'];
		}

		return '';
	}

	/**
	 * @inheritdoc
	 */
	public static function renderView(array $activity)
	{
		global $APPLICATION;

		if (!Loader::includeModule('voximplant'))
		{
			return '<div class="crm-task-list-call">
				<div class="crm-task-list-call-info">
					<div class="crm-task-list-call-info-container">
						<span class="crm-task-list-call-info-name">
							'.Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALL_DESCRIPTION').':
						</span>
					</div>
					<span>
						'.$activity['DESCRIPTION_HTML'].'
					</span>
				</div>
			</div>';
		}

		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:crm.activity.call',
			'',
			array(
				'ACTIVITY' => $activity,
				'CALL_ID' => (mb_strpos($activity['ORIGIN_ID'], 'VI_') === false? null : mb_substr($activity['ORIGIN_ID'], 3)),
			)
		);
		return ob_get_clean();
	}

	public static function getSupportedCommunicationStatistics()
	{
		return array(
			CommunicationStatistics::STATISTICS_QUANTITY,
			CommunicationStatistics::STATISTICS_STREAMS,
			CommunicationStatistics::STATISTICS_MARKS
		);
	}

	public static function onAfterAdd($activityFields, array $params = null)
	{
		\Bitrix\Crm\Integration\AI\EventHandler::onAfterCallActivityAdd($activityFields);

		$direction =
			isset($activityFields['DIRECTION'])
				? (int)$activityFields['DIRECTION']
				: \CCrmActivityDirection::Undefined
		;

		if ($direction === \CCrmActivityDirection::Outgoing && \Bitrix\Crm\Automation\Factory::canUseAutomation())
		{
			\Bitrix\Crm\Automation\Trigger\OutgoingCallTrigger::execute($activityFields['BINDINGS'], $activityFields);
		}
	}

	public static function onAfterUpdate(
		int $id,
		array $changedFields,
		array $oldFields,
		array $newFields,
		array $params = null
	)
	{
		\Bitrix\Crm\Integration\AI\EventHandler::onAfterCallActivityUpdate($changedFields, $oldFields, $newFields);
	}

	/**
	 * @inheritdoc
	 */
	public static function syncBadges(int $activityId, array $activityFields, array $bindings): void
	{
		$badge = Container::getInstance()->getBadge(
			Badge\Type\CallStatus::CALL_STATUS_TYPE,
			Badge\Type\CallStatus::MISSED_CALL_VALUE,
		);

		$sourceIdentifier = new Badge\SourceIdentifier(
			Badge\SourceIdentifier::CRM_OWNER_TYPE_PROVIDER,
			\CCrmOwnerType::Activity,
			$activityId,
		);

		$isCompleted = isset($activityFields['COMPLETED']) && $activityFields['COMPLETED'] === 'Y';
		if ($isCompleted)
		{
			foreach ($bindings as $singleBinding)
			{
				$itemIdentifier = new ItemIdentifier((int)$singleBinding['OWNER_TYPE_ID'], (int)$singleBinding['OWNER_ID']);
				$badge->unbind($itemIdentifier, $sourceIdentifier);
			}

			return;
		}

		$isMissed = isset($activityFields['SETTINGS']['MISSED_CALL']) && $activityFields['SETTINGS']['MISSED_CALL'] === true;
		if ($isMissed)
		{
			foreach ($bindings as $singleBinding)
			{
				$itemIdentifier = new ItemIdentifier((int)$singleBinding['OWNER_TYPE_ID'], (int)$singleBinding['OWNER_ID']);
				$badge->bind($itemIdentifier, $sourceIdentifier);
			}
		}
		else
		{
			foreach ($bindings as $singleBinding)
			{
				$itemIdentifier = new ItemIdentifier((int)$singleBinding['OWNER_TYPE_ID'], (int)$singleBinding['OWNER_ID']);
				$badge->unbind($itemIdentifier, $sourceIdentifier);
			}
		}
	}

	/**
	 * @param int $activityId Activity ID
	 * @param int $options    Fetch options [UNCOMPLETED_ACTIVITY_MISSED|UNCOMPLETED_ACTIVITY_INCOMING]
	 *
	 * @return array
	 */
	public static function getUncompletedActivityIdList(int $activityId, int $options = 0): array
	{
		$bindings = CCrmActivity::GetBindings($activityId);
		if (!is_array($bindings))
		{
			return [];
		}

		if (empty($bindings))
		{
			return [];
		}

		// fetch not completed CALL activities
		$dbResult = CCrmActivity::GetList(
			[],
			[
				'TYPE_ID' => CCrmActivityType::Call,
				'PROVIDER_ID' => static::getId(),
				'=PROVIDER_TYPE_ID' => static::ACTIVITY_PROVIDER_TYPE_CALL,
				'=CHECK_PERMISSIONS' => 'N',
				'=COMPLETED' => 'N',
				'BINDINGS' => $bindings,
			],
			false,
			false,
			['ID', 'SETTINGS'],
			[
				'QUERY_OPTIONS' => [
					'LIMIT' => 100,
				],
			],
		);

		$missedOnly = $options & self::UNCOMPLETED_ACTIVITY_MISSED;
		$incomingOnly = $options & self::UNCOMPLETED_ACTIVITY_INCOMING;

		$result = [];
		while ($arResult = $dbResult->Fetch())
		{
			if ($missedOnly)
			{
				$isMissedCall = isset($arResult['SETTINGS']['MISSED_CALL']) && $arResult['SETTINGS']['MISSED_CALL'];
				if ($isMissedCall)
				{
					$result[] = (int)$arResult['ID'];
				}
			}
			else
			{
				// all call activities excluding last created call activity
				if ($activityId !== (int)$arResult['ID'])
				{
					$result[] = (int)$arResult['ID'];
				}
			}
		}

		if ($incomingOnly)
		{
			$result = IncomingChannel::getInstance()->getIncomingChannelActivityIds($result);
		}

		return array_values(array_unique($result));
	}

	public static function hasPlanner(array $activity): bool
	{
		return !VoxImplantManager::isActivityBelongsToVoximplant($activity);
	}

	public static function hasRecordings(array $activity): bool
	{
		$storageTypeId = (int)($activity['STORAGE_TYPE_ID'] ?? null);

		$storageElementIds = \CCrmActivity::extractStorageElementIds($activity);

		return StorageType::isDefined($storageTypeId) && !empty($storageElementIds);
	}

	public static function getMoveBindingsLogMessageType(): ?string
	{
		return LogMessageType::CALL_MOVED;
	}
}
