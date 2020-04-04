<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main\ORM;
use Bitrix\Main\Context;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

use Bitrix\Crm\Ads;
use Bitrix\Crm\WebForm\Helper;
use Bitrix\Crm\WebForm\ResultEntity;
use Bitrix\Crm\WebForm\Entity as WebFormEntity;

Loc::loadMessages(__FILE__);

/**
 * Class FormTable
 *
 * @package Bitrix\Crm\WebForm\Internals
 */
class FormTable extends ORM\Data\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_webform';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'CODE' => array(
				'data_type' => 'string',
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => new DateTime(),
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'required' => true,
				'default_value' => 'Y',
				'values' => array('N', 'Y')
			),
			'ACTIVE_CHANGE_BY' => array(
				'data_type' => 'integer',
			),
			'ACTIVE_CHANGE_DATE' => array(
				'data_type' => 'datetime',
				'default_value' => new DateTime(),
			),
			'LANGUAGE_ID' => array(
				'data_type' => 'string',
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('CRM_WEBFORM_FORM_NAME'),
			),
			'CAPTION' => array(
				'data_type' => 'string',
			),
			'DESCRIPTION' => array(
				'data_type' => 'string',
			),
			'BUTTON_CAPTION' => array(
				'data_type' => 'string',
			),
			'BUTTON_COLOR_FONT' => array(
				'data_type' => 'string',
			),
			'BUTTON_COLOR_BG' => array(
				'data_type' => 'string',
			),
			'CSS_PATH' => array(
				'data_type' => 'string',
			),
			'CSS_TEXT' => array(
				'data_type' => 'text',
			),
			'BACKGROUND_IMAGE' => array(
				'data_type' => 'integer',
			),
			'TEMPLATE_ID' => array(
				'data_type' => 'enum',
				'required' => true,
				'default_value' => Helper::ENUM_TEMPLATE_LIGHT,
				'values' => array_keys(Helper::getTemplateList())
			),
			'ENTITY_SCHEME' => array(
				'data_type' => 'enum',
				'required' => true,
				'default_value' => WebFormEntity::ENUM_ENTITY_SCHEME_LEAD,
				'values' => WebFormEntity::getSchemesCodes()
			),
			'IS_PAY' => array(
				'data_type' => 'boolean',
				'required' => true,
				'default_value' => 'N',
				'values' => array('N','Y')
			),
			'DUPLICATE_MODE' => array(
				'data_type' => 'enum',
				'default_value' => ResultEntity::DUPLICATE_CONTROL_MODE_NONE,
				'values' => ResultEntity::getDuplicateModes()
			),
			'GOOGLE_ANALYTICS_ID' => array(
				'data_type' => 'string',
			),
			'GOOGLE_ANALYTICS_PAGE_VIEW' => array(
				'data_type' => 'boolean',
				'default_value' => 'N',
				'values' => array('N','Y')
			),
			'YANDEX_METRIC_ID' => array(
				'data_type' => 'string',
			),

			'RESULT_SUCCESS_TEXT' => array(
				'data_type' => 'string',
			),
			'RESULT_SUCCESS_URL' => array(
				'data_type' => 'string',
			),
			'RESULT_FAILURE_TEXT' => array(
				'data_type' => 'string',
			),
			'RESULT_FAILURE_URL' => array(
				'data_type' => 'string',
			),
			'USE_LICENCE' => array(
				'data_type' => 'boolean',
				'default_value' => 'N',
				'values' => array('N','Y')
			),
			'AGREEMENT_ID' => array(
				'data_type' => 'integer',
			),
			'LICENCE_BUTTON_IS_CHECKED' => array(
				'data_type' => 'boolean',
				'default_value' => 'N',
				'values' => array('N','Y')
			),
			'SCRIPT_INCLUDE_SETTINGS' => array(
				'data_type' => 'text',
				'serialized' => true
			),
			'INVOICE_SETTINGS' => array(
				'data_type' => 'text',
				'serialized' => true
			),
			'ASSIGNED_BY_ID' => array(
				'data_type' => 'integer',
			),

			'SECURITY_CODE' => array(
				'data_type' => 'string',
				'default_value' => function(){
					return Random::getString(6);
				}
			),

			'USE_CAPTCHA' => array(
				'data_type' => 'boolean',
				'required' => true,
				'default_value' => 'N',
				'values' => array('N','Y')
			),

			'IS_SYSTEM' => array(
				'data_type' => 'boolean',
				'required' => true,
				'default_value' => 'N',
				'values' => array('N','Y')
			),

			'XML_ID' => array(
				'data_type' => 'string',
			),

			'IS_CALLBACK_FORM' => array(
				'data_type' => 'boolean',
				'default_value' => 'N',
				'values' => array('N','Y')
			),

			'CALL_TEXT' => array(
				'data_type' => 'string',
			),

			'CALL_FROM' => array(
				'data_type' => 'string',
			),

			'FORM_SETTINGS' => array(
				'data_type' => 'string',
				'serialized' => true
			),

			'COPYRIGHT_REMOVED' => array(
				'data_type' => 'boolean',
				'required' => true,
				'default_value' => 'N',
				'values' => array('N', 'Y')
			),
		);
	}

	public static function onBeforeAdd(ORM\Event $event)
	{
		$fields = $event->getParameter('fields');
		$result = new ORM\EventResult();
		if(isset($fields['ENTITY_SCHEME']) && $fields['ENTITY_SCHEME'])
		{
			$fields['ENTITY_SCHEME'] = intval($fields['ENTITY_SCHEME']);
		}

		return $result;
	}

	public static function onAfterAdd(ORM\Event $event)
	{
		$result = new ORM\EventResult();
		$fields = $event->getParameter('fields');
		$data = $event->getParameters();
		$formId = $data['primary']['ID'];
		$fields['CAPTION'] = (isset($fields['CAPTION']) && $fields['CAPTION']) ? $fields['CAPTION'] : '';
		static::update($formId, array('CODE' => '', 'CAPTION' => $fields['CAPTION']));

		return $result;
	}

	public static function onBeforeUpdate(ORM\Event $event)
	{
		$fields = $event->getParameter('fields');
		$result = new ORM\EventResult();
		$data = $event->getParameters();
		$formId = $data['primary']['ID'];
		if(isset($fields['ENTITY_SCHEME']) && $fields['ENTITY_SCHEME'])
		{
			$fields['ENTITY_SCHEME'] = intval($fields['ENTITY_SCHEME']);
		}
		if(isset($fields['ACTIVE']) && $fields['ACTIVE'])
		{
			$oldData = static::getRowById($event->getParameter('id'));
			if($oldData['ACTIVE'] != $fields['ACTIVE'])
			{
				$result->modifyFields(array('ACTIVE_CHANGE_DATE' => new DateTime()));
			}
		}
		else
		{
			$result->unsetField('ACTIVE_CHANGE_BY');
		}

		if(isset($fields['CAPTION']))
		{
			$code = \CUtil::translit($fields['CAPTION'], Context::getCurrent()->getLanguage());
			$code = str_replace(array('"', "'", '`'), array("", "", ""), $code);
			$code = $formId . ($code ? '_' . $code : '');
			$result->modifyFields(array('CODE' => $code));
		}

		return $result;
	}

	/**
	 * @param ORM\Event $event Event
	 * @return ORM\EventResult Result
	 */
	public static function onDelete(ORM\Event $event)
	{
		$result = new ORM\EventResult;
		$data = $event->getParameters();
		$formId = $data['primary']['ID'];

		// delete Ads links
		Ads\AdsForm::unlinkForm($formId);

		// delete fields
		$fieldDb = FieldTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=FORM_ID' => $formId)
		));
		while($field = $fieldDb->fetch())
		{
			FieldTable::delete($field['ID']);
		}

		// delete field dependencies
		$fieldDependenceDb = FieldDependenceTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=FORM_ID' => $formId)
		));
		while($fieldDependence = $fieldDependenceDb->fetch())
		{
			FieldDependenceTable::delete($fieldDependence['ID']);
		}

		// delete preset fields
		PresetFieldTable::delete(array('FORM_ID' => $formId));
		// delete view statistics
		FormViewTable::delete(array('FORM_ID' => $formId));
		// delete start edit statistics
		FormStartEditTable::delete(array('FORM_ID' => $formId));

		// delete counters
		$formCounterDb = FormCounterTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=FORM_ID' => $formId)
		));
		while($formCounter = $formCounterDb->fetch())
		{
			FormCounterTable::delete($formCounter['ID']);
		}

		// delete results
		$resultRowDb = ResultTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=FORM_ID' => $formId)
		));
		while($resultRow = $resultRowDb->fetch())
		{
			ResultTable::delete($resultRow['ID']);
		}

		return $result;
	}

	public static function validateName()
	{
		return array(
			new ORM\Fields\Validators\LengthValidator(null, 50),
		);
	}
}
