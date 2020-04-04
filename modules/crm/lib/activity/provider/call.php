<?php

namespace Bitrix\Crm\Activity\Provider;


use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Settings\ActivitySettings;
use Bitrix\Crm\Activity\CommunicationStatistics;
use \Bitrix\Crm\Communication;
use Bitrix\Crm\Integration\VoxImplantManager;
use Bitrix\Voximplant\ConfigTable;

Loc::loadMessages(__FILE__);

class Call extends Base
{
	const ACTIVITY_PROVIDER_ID = 'VOXIMPLANT_CALL';
	const ACTIVITY_PROVIDER_TYPE_CALL = 'CALL';
	const ACTIVITY_PROVIDER_TYPE_CALLBACK = 'CALLBACK';

	public static function getId()
	{
		return static::ACTIVITY_PROVIDER_ID;
	}

	public static function isActive()
	{
		$result = false;
		if(Loader::includeModule('voximplant'))
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
		if(!Loader::includeModule('voximplant'))
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
			return $activity['PROVIDER_TYPE_ID'];
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
		// Call presets is already in filter (compatible TYPE_ID = \CCrmActivityType::Call)
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
		if ($action === 'UPDATE')
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

	public static function canUseCalendarEvents($providerTypeId = null)
	{
		$result = false;
		if($providerTypeId === static::ACTIVITY_PROVIDER_TYPE_CALL)
			$result = true;
		else if($providerTypeId === static::ACTIVITY_PROVIDER_TYPE_CALLBACK)
			$result = false;
		
		return $result;
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
		$result = false;
		if($providerTypeId === static::ACTIVITY_PROVIDER_TYPE_CALL)
			$result = true;
		else if($providerTypeId === static::ACTIVITY_PROVIDER_TYPE_CALLBACK)
			$result = false;

		return $result;
	}

	/**
	 * @param array $params Activity params.
	 * @return array Actions list.
	 */
	public static function getPlannerActions(array $params = null)
	{
		return array(
			array(
				'ACTION_ID' => self::getId().'_'.self::ACTIVITY_PROVIDER_TYPE_CALL,
				'NAME' => Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALL_PLANNER_ACTION_NAME'),
				'TYPE_ID' => \CCrmActivityType::Call,
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
		if($direction === \CCrmActivityDirection::Incoming)
		{
			return Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALL_INCOMING_SUBJECT', $replace);
		}
		elseif($direction === \CCrmActivityDirection::Outgoing)
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
				'VALUE' => isset($activity['SUBJECT']) ? $activity['SUBJECT'] : ''
			)
		);

		$callId = strpos($activity['ORIGIN_ID'], 'VI_') === false ? null : substr($activity['ORIGIN_ID'], 3);
		$callInfo = VoxImplantManager::getCallInfo($callId);
		if($callInfo)
		{
			$fields[] = array(
				'LABEL' => Loc::getMessage('VOXIMPLANT_ACTIVITY_PROVIDER_CALL_COMMENT'),
				'TYPE' => 'TEXT',
				'NAME' => 'COMMENT',
				'VALUE' => isset($callInfo['COMMENT']) ? $callInfo['COMMENT'] : ''
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
			$activity['PROVIDER_TYPE_ID'] = static::ACTIVITY_PROVIDER_TYPE_CALL;
	}

	public static function postForm(array &$activity, array $formData)
	{
		$result = new Main\Result();
		if($formData['comment'])
		{
			$activityId = $formData['id'];
			$activityFields = \CCrmActivity::GetByID($activityId, false);

			$callId = strpos($activityFields['ORIGIN_ID'], 'VI_') === false ? null : substr($activityFields['ORIGIN_ID'], 3);
			if($callId)
			{
				VoxImplantManager::saveComment($callId, $formData['comment']);
			}
		}

		if (is_array($activity['COMMUNICATIONS']))
		{
			foreach ($activity['COMMUNICATIONS'] as $k => $v)
			{
				if($activity['COMMUNICATIONS'][$k]['TYPE'] == '' && $activity['COMMUNICATIONS'][$k]['VALUE'] == '')
				{
					$firstNumber = static::getFirstPhoneNumber($activity['COMMUNICATIONS'][$k]['ENTITY_TYPE_ID'], $activity['COMMUNICATIONS'][$k]['ENTITY_ID']);
					if($firstNumber == '')
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

		if(is_array($phones) && count($phones) > 0)
		{
			return $phones[0]['VALUE'];
		}
		else
		{
			return '';
		}
	}

	/**
	 * @inheritdoc
	 */
	public static function renderView(array $activity)
	{
		global $APPLICATION;
		
		if(!Loader::includeModule('voximplant'))
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
				'CALL_ID' => (strpos($activity['ORIGIN_ID'], 'VI_') === false ? null : substr($activity['ORIGIN_ID'], 3)),
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
}