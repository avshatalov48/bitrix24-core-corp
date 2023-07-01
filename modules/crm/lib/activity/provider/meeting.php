<?php
namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity;
use Bitrix\Crm\Activity\CommunicationStatistics;
use Bitrix\Crm\Settings\ActivitySettings;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Meeting extends Activity\Provider\Base
{
	public static function getId()
	{
		return 'CRM_MEETING';
	}

	public static function getTypeId(array $activity)
	{
		return 'MEETING';
	}

	public static function getTypes()
	{
		return array(
			array(
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_MEETING_NAME'),
				'PROVIDER_ID' => static::getId(),
				'PROVIDER_TYPE_ID' => 'MEETING'
			)
		);
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_MEETING_NAME');
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

	public static function canUseCalendarEvents($providerTypeId = null)
	{
		return true;
	}

	public static function canKeepCompletedInCalendar($providerTypeId = null)
	{
		return ActivitySettings::getValue(ActivitySettings::KEEP_COMPLETED_MEETINGS);
	}

	public static function canKeepReassignedInCalendar($providerTypeId = null)
	{
		return ActivitySettings::getValue(ActivitySettings::KEEP_REASSIGNED_MEETINGS);
	}

	/**
	 * @param array $params Activity params.
	 * @return array Actions list.
	 */
	public static function getPlannerActions(array $params = null)
	{
		if (!\Bitrix\Crm\Settings\ActivitySettings::areOutdatedCalendarActivitiesEnabled())
		{
			return [];
		}

		return array(
			array(
				'ACTION_ID' => static::getId().'_MEETING',
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_MEETING_PLANNER_ACTION_NAME'),
				'TYPE_ID' => \CCrmActivityType::Meeting
			)
		);
	}

	/**
	 * @param array $activity Activity data.
	 * @return string Title.
	 */
	public static function getPlannerTitle(array $activity)
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_MEETING_PLANNER_ACTION_NAME');
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @param int $direction Activity direction.
	 * @param array|null $replace Message replace templates.
	 * @return string
	 */
	public static function generateSubject($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined, array $replace = null)
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_MEETING_SUBJECT', $replace);
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
				'LABEL' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_MEETING_PLANNER_SUBJECT_LABEL'),
				'TYPE' => 'SUBJECT',
				'VALUE' => isset($activity['SUBJECT']) ? $activity['SUBJECT'] : ''
			),
			array(
				'LABEL' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_MEETING_PLANNER_LOCATION_LABEL'),
				'TYPE' => 'LOCATION',
				'VALUE' => isset($activity['LOCATION']) ? $activity['LOCATION'] : ''
			)
		);

		return array_merge($fields, $parentFields);
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @param int $direction Activity direction.
	 * @return bool
	 */
	public static function getTypeName($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_MEETING_NAME');
	}

	/**
	 * @param array $activity Activity data.
	 * @return string Rendered html view for specified mode.
	 */
	public static function renderView(array $activity)
	{
		$html = '<div class="crm-task-list-meet">';

		if (!empty($activity['SUBJECT']))
		{
			$html .= '<div class="crm-task-list-meet-inner">
					<div class="crm-task-list-meet-item">'.Loc::getMessage('CRM_ACTIVITY_PROVIDER_MEETING_PLANNER_SUBJECT_LABEL').':</div>
					<div class="crm-task-list-meet-topic">'.htmlspecialcharsbx($activity['SUBJECT']).'</div>
				</div>';
		}
		if (!empty($activity['DESCRIPTION']))
		{
			$html .= '<div class="crm-task-list-meet-inner">
					<div class="crm-task-list-meet-item">'.Loc::getMessage('CRM_ACTIVITY_PROVIDER_MEETING_PLANNER_DESCRIPTION_LABEL').':</div>
					<div class="crm-task-list-meet-element">'.$activity['DESCRIPTION_HTML'].'</div>
				</div>';
		}
		if (!empty($activity['LOCATION']))
		{
			if (Main\Loader::includeModule('calendar'))
			{
				$activity['LOCATION'] = \CCalendar::GetTextLocation($activity['LOCATION']);
			}

			$html .= '<div class="crm-task-list-meet-inner">
					<div class="crm-task-list-meet-item">'.Loc::getMessage('CRM_ACTIVITY_PROVIDER_MEETING_PLANNER_PLACE_LABEL').':</div>
					<div class="crm-task-list-meet-element">'.htmlspecialcharsbx($activity['LOCATION']).'</div>
				</div>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * @param array $activity
	 */
	public static function fillDefaultActivityFields(array &$activity)
	{
		$activity['NOTIFY_TYPE'] = \CCrmActivityNotifyType::Hour;
		$activity['NOTIFY_VALUE'] = 1;
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @return bool
	 */
	public static function canUseLiveFeedEvents($providerTypeId = null)
	{
		return true;
	}

	public static function getSupportedCommunicationStatistics()
	{
		return array(
			CommunicationStatistics::STATISTICS_QUANTITY
		);
	}
}
