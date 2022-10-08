<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\CallList\Internals\CallListTable;
use Bitrix\Crm\WebForm\Internals\FormTable;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CallList extends Base
{
	const PROVIDER_ID = 'CALL_LIST';
	const TYPE_CALL_LIST = 'CALL_LIST';

	/**
	 * @inheritdoc
	 */
	public static function getId()
	{
		return self::PROVIDER_ID;
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_CALL_LIST_TITLE');
	}

	/**
	 * @param array $activity
	 * @return string
	 */
	public static function getPlannerTitle(array $activity)
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_CALL_LIST_TITLE');
	}

	/**
	 * Returns supported provider's types
	 * @return array
	 */
	public static function getTypes()
	{
		return array(
			array(
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_CALL_LIST_TITLE'),
				'PROVIDER_ID' => static::PROVIDER_ID,
				'PROVIDER_TYPE_ID' => static::TYPE_CALL_LIST,
			),
		);
	}

	/**
	 * @param array $params Activity params.
	 * @return array Actions list.
	 */
	public static function getPlannerActions(array $params = null)
	{
		if($params['OWNER_TYPE_ID'] > 0 && $params['OWNER_ID'] > 0)
		{
			return array();
		}
		else if(\Bitrix\Crm\CallList\CallList::isAvailable())
		{
			return array(
				array(
					'ACTION_ID' => static::getId().'_ACTIVITY',
					'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_CALL_LIST_TITLE'),
					'PROVIDER_ID' => static::getId(),
					'PROVIDER_TYPE_ID' => static::TYPE_CALL_LIST
				)
			);
		}
		else
		{
			return array();
		}
	}

	/**
	 * @return int
	 */
	public static function prepareToolbarButtons(array &$buttons, array $params = null)
	{
		if(\Bitrix\Crm\CallList\CallList::isAvailable())
		{
			return 0;
		}
		else if($params['OWNER_TYPE_ID'] > 0 && $params['OWNER_ID'] > 0)
		{
			return 0;
		}
		else
		{
			$buttons[] = array(
				'TEXT' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_CALL_LIST_TITLE'),
				'TITLE' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_CALL_LIST_TITLE'),
				'ONCLICK' => \Bitrix\Crm\Restriction\RestrictionManager::getCallListRestriction()->prepareInfoHelperScript(),
				'ICON' => "btn-new"
			);
			return 1;
		}
	}

	public static function getFieldsForEdit(array $activity)
	{
		global $APPLICATION;
		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:crm.activity.call_list',
			'',
			array(
				'ACTIVITY' => $activity,
				'ACTION' => 'EDIT'
			)
		);

		return array(
			array(
				'HTML' => ob_get_clean()
			),
		);
	}

	public static function getAdditionalFieldsForEdit(array $activity)
	{
		return array(
			array('TYPE' => 'RESPONSIBLE'),
			array(
				'TYPE' => 'HTML',
				'HTML' => static::getWebformsHtml($activity)
			)
		);
	}

	public static function getWebformsHtml($activity)
	{
		if($activity['ASSOCIATED_ENTITY_ID'] > 0)
			$callList = CallListTable::getById($activity['ASSOCIATED_ENTITY_ID'])->fetch();
		else
			$callList = array(
				'WEBFORM_ID' => 0
			);

		$forms = FormTable::getDefaultTypeList(array(
			'select' => array('ID', 'NAME'),
			'filter' => array('ACTIVE' => 'Y')
		));
		$result ='
			<div class="crm-activity-popup-info-person-detail-calendar" style="margin-bottom: 20px;">
				<input type="hidden" name="useWebform" value="N">
				<input type="checkbox" name="useWebform" value="Y" class="crm-activity-popup-timeline-checkbox" id="crm-activity-popup-use-webform" '.($callList['WEBFORM_ID'] > 0 ? 'checked' : '').'>
				<label for="crm-activity-popup-use-webform">'.GetMessage('CRM_CALL_LIST_USE_WEBFORM').':</label>
				<select name="webformId" class="crm-activity-popup-info-webform-input">
				';

				foreach ($forms as $form)
				{
					$result .= '<option value="'.htmlspecialcharsbx($form['ID']).'" '.($callList['WEBFORM_ID'] == $form['ID'] ? 'selected' : '').'>'.htmlspecialcharsbx($form['NAME']).'</option>';
				}

		$result .= '
				</select>
			</div>';
		return $result;
	}

	/**
	 * @inheritdoc
	 */
	public static function renderView(array $activity)
	{
		global $APPLICATION;
		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:crm.activity.call_list',
			'',
			array(
				'ACTIVITY' => $activity,
				'ACTION' => 'VIEW'
			)
		);
		return ob_get_clean();
	}
	
	public static function checkOwner()
	{
		return false;
	}
	
	public static function postForm(array &$activity, array $formData)
	{
		$result = new Main\Result();
		$callListId = (int)$formData['callListId'];
		$callListSubject = (string)$formData['callListSubject'];
		$callListDescription = (string)$formData['callListDescription'];

		if($callListId === 0)
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_CALL_LIST_NOT_CREATED_ERROR')));
			return $result;
		}
		
		if($callListSubject == '')
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_CALL_LIST_SUBJECT_EMPTY')));
			return $result;
		}

		try
		{
			$callList = \Bitrix\Crm\CallList\CallList::createWithId($callListId);
		}
		catch (\Bitrix\Main\SystemException $e)
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_CALL_LIST_NOT_CREATED_ERROR')));
			return $result;
		}

		if($callList->getItemsCount() == 0)
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_CALL_LIST_NOT_CREATED_ERROR')));
			return $result;
		}

		$webformId = $formData['useWebform'] === 'Y' ? (int)$formData['webformId'] : null;
		$callList->setWebformId($webformId);
		$callList->persist();

		$activity['ASSOCIATED_ENTITY_ID'] = $callListId;
		//$activity['BINDINGS'] = $callList->convertItemsToBindings();
		$activity['SUBJECT'] = $callListSubject;
		$activity['DESCRIPTION'] = $callListDescription;
		$activity['OWNER_TYPE_ID'] = \CCrmOwnerType::CallList;
		$activity['OWNER_ID'] = $callListId;

		$activity['BINDINGS'] = array(
			array(
				'OWNER_TYPE_ID' => \CCrmOwnerType::CallList,
				'OWNER_ID' => $callListId
			)
		);

		return $result;
	}

	/**
	 * Returns provider types for usage in the activities filter.
	 * @return array
	 */
	public static function getTypesFilterPresets()
	{
		// Call presets is already in filter (compatible TYPE_ID = \CCrmActivityType::Call)
		// Add Callback only.
		return array(
			array(
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_CALL_LIST_TITLE'),
				'PROVIDER_TYPE_ID' =>  static::TYPE_CALL_LIST,
			)
		);
	}

	public static function onAfterAdd($activityFields, array $params = null)
	{
		if($activityFields['AUTHOR_ID'] !== $activityFields['RESPONSIBLE_ID'])
			static::notify($activityFields);
	}
	
	public static function notify($activityFields)
	{
		if(!Main\Loader::includeModule('im'))
			return;

		$notification = array(
			"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
			"TO_USER_ID" => $activityFields['RESPONSIBLE_ID'],
			"FROM_USER_ID" => $activityFields['AUTHOR_ID'],
			"NOTIFY_TYPE" => IM_NOTIFY_FROM,
			"NOTIFY_MODULE" => "crm",
			//"NOTIFY_EVENT" => "callListCreated",
			"NOTIFY_EVENT" => "changeAssignedBy",
			"NOTIFY_TAG" => "CRM|CALL_LIST|".$activityFields['ID'],
			"NOTIFY_MESSAGE" => Loc::getMessage('CRM_CALL_LIST_RESPONSIBLE_IM_NOTIFY', array(
				'#title#' =>  '<a href="'.\CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Activity, $activityFields['ID']).'">'.$activityFields['SUBJECT'].'</a>'
			)),
			"NOTIFY_MESSAGE_OUT" => Loc::getMessage('CRM_CALL_LIST_RESPONSIBLE_IM_NOTIFY', array(
				'#title#' => $activityFields['SUBJECT']
			)),
		);
		
		\CIMNotify::Add($notification);
	}

	/**
	 * Returns update permission for a callList activity. Returns true if specified user has update permission for,
	 * at least one bound entity.
	 * @param array $activityFields Fields of the activity.
	 * @param null $userId Id of the user.
	 * @return bool
	 */
	public static function checkUpdatePermission(array $activityFields, $userId = null)
	{
		$callListId = $activityFields['ASSOCIATED_ENTITY_ID'];
		try
		{
			$callList = \Bitrix\Crm\CallList\CallList::createWithId($callListId, true);
		}
		catch (Main\SystemException $e)
		{
			return false;
		}

		$permission = \CCrmPerms::GetUserPermissions($userId);
		if(!is_array($callList->getItems()) || count($callList->getItems()) == 0)
			return true;

		foreach ($callList->getItems() as $callListItem)
		{
			if(\CCrmActivity::CheckReadPermission($callList->getEntityTypeId(), $callListItem->getElementId(), $permission))
			{
				return true;
			}
		}

		return false;
	}

	public static function checkReadPermission(array $activityFields, $userId = null)
	{
		if (!parent::checkReadPermission($activityFields, $userId))
		{
			return false;
		}
		$callListId = $activityFields['ASSOCIATED_ENTITY_ID'];

		try
		{
			$callList = \Bitrix\Crm\CallList\CallList::createWithId($callListId, true);
		}
		catch (Main\SystemException $e)
		{
			return false;
		}

		$permission = \CCrmPerms::GetUserPermissions($userId);
		if (!is_array($callList->getItems()) || count($callList->getItems()) === 0)
		{
			return false;
		}

		foreach ($callList->getItems() as $callListItem)
		{
			if(\CCrmActivity::CheckReadPermission($callList->getEntityTypeId(), $callListItem->getElementId(), $permission))
			{
				return true;
			}
		}

		return false;
	}

	public static function canUseCalendarEvents($providerTypeId = null)
	{
		return true;
	}

	public static function transferOwnership($oldEntityTypeId, $oldEntityId, $newEntityTypeId, $newEntityId)
	{
		\Bitrix\Crm\CallList\CallList::transferOwnership($oldEntityTypeId, $oldEntityId, $newEntityTypeId, $newEntityId);
	}

	public static function deleteByOwner($entityTypeId, $entityId)
	{
		\Bitrix\Crm\CallList\CallList::deleteByOwner($entityTypeId, $entityId);
	}
}