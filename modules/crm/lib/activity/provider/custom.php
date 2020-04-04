<?php
namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Activity\CustomType;

Loc::loadMessages(__FILE__);

class Custom extends Base
{
	const PROVIDER_ID = 'CUST';

	public static function getId()
	{
		return static::PROVIDER_ID;
	}
	public static function getTypes()
	{
		$results = array();
		foreach(CustomType::getAll() as $entry)
		{
			$results[] = array(
				'NAME' => $entry['NAME'],
				'PROVIDER_ID' => static::PROVIDER_ID,
				'PROVIDER_TYPE_ID' => (int)$results['ID'],
				'DIRECTIONS' => array()
			);
		}
		return $results;
	}

	/**
	 * @param array $activity Activity data.
	 * @return string Title.
	 */
	public static function getPlannerTitle(array $activity)
	{
		$providerID = isset($activity['PROVIDER_ID']) ? $activity['PROVIDER_ID'] : '';
		if($providerID !== self::PROVIDER_ID)
		{
			return '';
		}

		$providerTypeID = isset($activity['PROVIDER_TYPE_ID']) ? (int)$activity['PROVIDER_TYPE_ID'] : 0;
		$typeFields = $providerTypeID > 0 ? CustomType::get($providerTypeID) : null;
		return is_array($typeFields) && isset($typeFields['NAME']) ? $typeFields['NAME'] : '';
	}

	/**
	 * @return int
	 */
	public static function prepareToolbarButtons(array &$buttons, array $params = null)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		$selectorID = isset($params['UID']) ? $params['UID'] : '';
		if($selectorID === '')
		{
			$selectorID = 'current';
		}
		$selectorID = \CUtil::JSEscape($selectorID);
		$ownerTypeID = isset($params['OWNER_TYPE_ID']) ? (int)$params['OWNER_TYPE_ID'] : \CCrmOwnerType::Undefined;
		$ownerID = isset($params['OWNER_ID']) ? (int)$params['OWNER_ID'] : 0;
		$infos = \CUtil::PhpToJSObject(CustomType::getJavaScriptInfos());

		$buttons[] = array(
			'TEXT' => Loc::getMessage('CRM_ACTIVITY_PVDR_CUST_ACTION_BUTTON'),
			'TITLE' => Loc::getMessage('CRM_ACTIVITY_PVDR_CUST_ACTION_BUTTON_TITLE'),
			'ONCLICK' => "BX.CrmCustomActivityTypeSelector.items['{$selectorID}'].openMenu(this)",
			'TYPE' => 'crm-context-menu',
			'PARAMS' => array(
				'SCRIPTS' => array(
					"BX.CrmCustomActivityType.infos = {$infos}",
					"BX.CrmCustomActivityTypeSelector.create(\"{$selectorID}\", { ownerTypeId: {$ownerTypeID}, ownerId: {$ownerID} })"
				)
			)
		);
		return 1;
	}
	/**
	 * @param array $activity Activity data.
	 * @return array Fields.
	 */
	public static function getFieldsForEdit(array $activity)
	{
		/** @var \CAllMain $APPLICATION */
		/** @var \CAllUserTypeManager $USER_FIELD_MANAGER */
		global $APPLICATION, $USER_FIELD_MANAGER;

		$results = array(
			array(
				'LABEL' => Loc::getMessage('CRM_ACTIVITY_PVDR_CUST_SUBJECT_LABEL'),
				'TYPE' => 'SUBJECT',
				'VALUE' => isset($activity['SUBJECT']) ? $activity['SUBJECT'] : ''
			)
		);

		$typeID = isset($activity['PROVIDER_TYPE_ID']) ? (int)$activity['PROVIDER_TYPE_ID'] : 0;
		if($typeID <= 0)
		{
			return $results;
		}

		$entityID = CustomType::prepareUserFieldEntityID($typeID);
		$fields = $USER_FIELD_MANAGER->getUserFields($entityID, $activity['ID'], LANGUAGE_ID);

		foreach($fields as $field)
		{
			$html = '<div class="crm-activity-popup-info-location-container">';
			$html .= '<div class="crm-activity-popup-info-location-text" style="margin-bottom: 10px;">';

			if(isset($field['MANDATORY']) && $field['MANDATORY'] === 'Y')
			{
				$html .= '<span class="required">*</span>';
			}

			$html .= htmlspecialcharsbx(isset($field['EDIT_FORM_LABEL']) ? $field['EDIT_FORM_LABEL'] : $field['FIELD_NAME']);
			$html .= ':</div>';

			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:system.field.edit',
				$field['USER_TYPE']['USER_TYPE_ID'],
				array('bVarsFromForm' => false, 'arUserField' => $field, 'form_name' => 'activity-edit-form'),
				null,
				array('HIDE_ICONS' => 'Y')
			);
			$html .= ob_get_clean();
			$html .= '</div>';
			$results[] = array('HTML' => $html);
		}

		$parentFields = parent::getFieldsForEdit($activity);
		return array_merge($results, $parentFields);
	}
	/**
	 * @param array $activity Activity data.
	 * @param array $formData Request post data.
	 * @return Main\Result Post result.
	 */
	public static function postForm(array &$activity, array $formData)
	{
		/** @var \CAllMain $APPLICATION */
		/** @var \CAllUserTypeManager $USER_FIELD_MANAGER */
		global $APPLICATION, $USER_FIELD_MANAGER;

		$entityID = CustomType::prepareUserFieldEntityID(
			isset($activity['PROVIDER_TYPE_ID']) ? $activity['PROVIDER_TYPE_ID'] : 0
		);

		if($entityID === '')
		{
			return new Main\Result();
		}

		$result = new Main\Result();
		$fields = array();
		$USER_FIELD_MANAGER->editFormAddFields($entityID, $fields, array('FORM' => $formData));
		if(!$USER_FIELD_MANAGER->checkFields($entityID, $activity['ID'], $fields))
		{
			$e = $APPLICATION->getException();
			if($e instanceof \CAdminException)
			{
				/** @var \CAdminException $e */
				foreach($e->GetMessages() as $msg)
				{
					if(isset($msg['text']))
					{
						$result->addError(new Main\Error($msg['text']));
					}
				}
			}
			else
			{
				/** @var \CApplicationException $e */
				$result->addError(new Main\Error($e->GetString()));
			}
		}

		$activity['FM'] = $fields;
		return $result;
	}
	/**
	 * @param int $ID Activity ID.
	 * @param array $data Activity data.
	 * @return Main\Result Save result.
	 */
	public static function saveAdditionalData($ID, array $data)
	{
		/** @var \CAllUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;

		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		if($ID <= 0 && isset($data[$ID]))
		{
			$ID = (int)$data[$ID];
		}

		if($ID <= 0)
		{
			return new Main\Result();
		}

		$entityID = CustomType::prepareUserFieldEntityID(
			isset($data['PROVIDER_TYPE_ID']) ? $data['PROVIDER_TYPE_ID'] : 0
		);

		if($entityID !== '' && isset($data['FM']) && is_array($data['FM']))
		{
			$USER_FIELD_MANAGER->update($entityID, $ID, $data['FM']);
		}

		return new Main\Result();
	}
	/**
	 * @param array $activity Activity data.
	 * @return string Rendered html view for specified mode.
	 */
	public static function renderView(array $activity)
	{
		/** @var \CAllMain $APPLICATION */
		/** @var \CAllUserTypeManager $USER_FIELD_MANAGER */
		global $APPLICATION, $USER_FIELD_MANAGER;
		$typeID = isset($activity['PROVIDER_TYPE_ID']) ? (int)$activity['PROVIDER_TYPE_ID'] : 0;
		if($typeID <= 0)
		{
			return '';
		}

		$entityID = CustomType::prepareUserFieldEntityID($typeID);
		$fields = $USER_FIELD_MANAGER->getUserFields($entityID, $activity['ID'], LANGUAGE_ID);

		$html = '<div class="crm-task-list-meet">';
		foreach($fields as $field)
		{
			$html .= '<div class="crm-task-list-meet-inner">';

			$html .= '<div class="crm-task-list-meet-item">';
			$html .= htmlspecialcharsbx(isset($field['EDIT_FORM_LABEL']) ? $field['EDIT_FORM_LABEL'] : $field['FIELD_NAME']);
			$html .= ':</div>';

			$html .= '<div class="crm-task-list-meet-element">';
			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:system.field.view',
				$field['USER_TYPE']['USER_TYPE_ID'],
				array('arUserField' => $field),
				null,
				array('HIDE_ICONS' => 'Y')
			);
			$html .= ob_get_clean();
			$html .= '</div>';
			$html .= '</div>';
		}
		$html .= '</div>';
		return $html;
	}

	public static function isTypeEditable($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return true;
	}
}