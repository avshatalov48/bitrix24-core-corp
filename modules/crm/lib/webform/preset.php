<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Crm\Integration\UserConsent;

Loc::loadMessages(__FILE__);

class Preset
{
	protected $errors = array();
	protected static $version = 2;
	protected static $versionOptionName = 'webform_preset_version';

	protected static function getVersion()
	{
		return self::$version;
	}

	protected static function getInstalledVersion()
	{
		return (int) Option::get('crm', self::$versionOptionName, 0);
	}

	public static function updateInstalledVersion($version = null)
	{
		if($version === null)
		{
			$version = self::getVersion();
		}

		Option::set('crm', self::$versionOptionName, $version);
	}

	public static function checkVersion()
	{
		return self::getVersion() > self::getInstalledVersion();
	}

	public function getErrors()
	{
		return $this->errors;
	}

	public function hasErrors()
	{
		return count($this->errors) > 0;
	}

	public function isInstalled($xmlId)
	{
		$formDb = Internals\FormTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=IS_SYSTEM' => 'Y', '=XML_ID' => $xmlId),
		));
		if($formDb->fetch())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function install()
	{
		if(!self::checkVersion())
		{
			return true;
		}

		$presets = static::getList();
		foreach($presets as $preset)
		{
			if($this->isInstalled($preset['XML_ID']))
			{
				continue;
			}

			$this->addForm($preset);
		}

		if(!$this->hasErrors())
		{
			self::updateInstalledVersion();
		}

		$callback = array(__CLASS__, 'installVersion' . self::getVersion());
		if (is_callable($callback))
		{
			call_user_func_array($callback, array());
		}

		return $this->hasErrors();
	}

	public function uninstall($xmlId = null)
	{
		$filter = array('=IS_SYSTEM' => 'Y');
		if($xmlId)
		{
			$filter['=XML_ID'] = $xmlId;
		}
		$formDb = Internals\FormTable::getList(array(
			'select' => array('ID'),
			'filter' => $filter,
		));
		while($form = $formDb->fetch())
		{
			$deleteDb = Internals\FormTable::delete($form['ID']);
			if(!$deleteDb->isSuccess())
			{
				$this->errors = array_merge($this->errors, $deleteDb->getErrorMessages());
			}
		}

		if(!$xmlId)
		{
			self::updateInstalledVersion(0);
		}
	}

	protected function addForm($formData)
	{
		$formData['IS_SYSTEM'] = 'Y';
		$formData['ACTIVE_CHANGE_BY'] = self::getCurrentUserId();
		$formData['ASSIGNED_BY_ID'] = self::getCurrentUserId();
		$formData['ACTIVE'] = 'Y';

		$agreementId = UserConsent::getDefaultAgreementId();
		$formData['USE_LICENCE'] = $agreementId ? 'Y': 'N';
		if ($agreementId)
		{
			$formData['LICENCE_BUTTON_IS_CHECKED'] = 'Y';
			$formData['AGREEMENT_ID'] = $agreementId;
		}

		$form = new Form;
		if(!$formData['BUTTON_CAPTION'])
		{
			$formData['BUTTON_CAPTION'] = $form->getButtonCaption();
		}

		$form->merge($formData);
		$form->save();
		$this->errors = array_merge($this->errors, $form->getErrors());

		return $form->hasErrors();
	}

	protected static function getCurrentUserId()
	{
		static $userId = null;
		if($userId === null)
		{
			global $USER;
			$userId = (is_object($USER) && $USER->GetID()) ? $USER->GetID() : 1;
		}

		return $userId;
	}

	public static function getById($xmlId)
	{
		$presets = static::getList();
		foreach($presets as $preset)
		{
			if($preset['ID'] == $xmlId)
			{
				return $preset;
			}
		}

		return null;
	}

	public static function getList()
	{
		$list = array(
			array(
				'XML_ID' => 'crm_preset_cd', //cd - ContactData
				'NAME' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CD_NAME'),
				'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CD_CAPTION'),
				'DESCRIPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CD_DESCRIPTION'),
				'RESULT_SUCCESS_TEXT' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_RESULT_SUCCESS_TEXT'),
				'RESULT_FAILURE_TEXT' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_RESULT_FAILURE_TEXT'),
				'ENTITY_SCHEME' => (string) Entity::ENUM_ENTITY_SCHEME_LEAD,
				'TEMPLATE_ID' => Helper::ENUM_TEMPLATE_LIGHT,
				'COPYRIGHT_REMOVED' => 'N',
				'IS_PAY' => 'N',
				'DUPLICATE_MODE' => ResultEntity::DUPLICATE_CONTROL_MODE_NONE,
				'BUTTON_CAPTION' => '',
				'FIELDS' => array(
					array(
						'TYPE' => 'string',
						'CODE' => 'LEAD_NAME',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_NAME'),
						'SORT' => 100,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					),
					array(
						'TYPE' => 'string',
						'CODE' => 'LEAD_LAST_NAME',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_LAST_NAME'),
						'SORT' => 200,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					),
					array(
						'TYPE' => 'phone',
						'CODE' => 'LEAD_PHONE',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_PHONE'),
						'SORT' => 300,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					),
					array(
						'TYPE' => 'email',
						'CODE' => 'LEAD_EMAIL',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_EMAIL'),
						'SORT' => 400,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					)
				)
			),
			array(
				'XML_ID' => 'crm_preset_fb', //fb - FeedBack
				'NAME' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_FB_NAME'),
				'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_FB_CAPTION'),
				'DESCRIPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_FB_DESCRIPTION'),
				'RESULT_SUCCESS_TEXT' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_RESULT_SUCCESS_TEXT'),
				'RESULT_FAILURE_TEXT' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_RESULT_FAILURE_TEXT'),
				'ENTITY_SCHEME' => (string) Entity::ENUM_ENTITY_SCHEME_LEAD,
				'TEMPLATE_ID' => Helper::ENUM_TEMPLATE_LIGHT,
				'COPYRIGHT_REMOVED' => 'N',
				'IS_PAY' => 'N',
				'DUPLICATE_MODE' => ResultEntity::DUPLICATE_CONTROL_MODE_NONE,
				'BUTTON_CAPTION' => '',
				'FIELDS' => array(
					array(
						'TYPE' => 'string',
						'CODE' => 'LEAD_NAME',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_NAME'),
						'SORT' => 100,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					),
					array(
						'TYPE' => 'string',
						'CODE' => 'LEAD_LAST_NAME',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_LAST_NAME'),
						'SORT' => 200,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					),
					array(
						'TYPE' => 'phone',
						'CODE' => 'LEAD_PHONE',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_PHONE'),
						'SORT' => 300,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					),
					array(
						'TYPE' => 'email',
						'CODE' => 'LEAD_EMAIL',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_EMAIL'),
						'SORT' => 400,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					),
					array(
						'TYPE' => 'text',
						'CODE' => 'LEAD_COMMENTS',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_COMMENTS'),
						'SORT' => 500,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					)
				)
			)
		);

		if (Loader::includeModule('voximplant'))
		{
			$callbackNumbers = Callback::getPhoneNumbers();
			foreach($callbackNumbers as $number)
			{
				$list[] = self::getCallback($number['CODE'], $number['NAME']);
			}
		}

		return $list;
	}

	protected static function getCallback($phoneCode, $phoneCaption)
	{
		$callback = array(
			'XML_ID' => 'crm_preset_cb_' . $phoneCode, //cb - CallBack
			'NAME' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CB_NAME', array('#call_from#' => $phoneCaption)),
			'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CB_CAPTION'),
			'DESCRIPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CB_DESCRIPTION'),
			'RESULT_SUCCESS_TEXT' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CB_RESULT_SUCCESS_TEXT'),
			'RESULT_FAILURE_TEXT' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_RESULT_FAILURE_TEXT'),
			'ENTITY_SCHEME' => (string) Entity::ENUM_ENTITY_SCHEME_LEAD,
			'TEMPLATE_ID' => Helper::ENUM_TEMPLATE_LIGHT,
			'COPYRIGHT_REMOVED' => 'N',
			'IS_PAY' => 'N',
			'IS_CALLBACK_FORM' => 'Y',
			'CALL_FROM' => $phoneCode,
			'CALL_TEXT' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CB_CALL_TEXT'),
			'DUPLICATE_MODE' => ResultEntity::DUPLICATE_CONTROL_MODE_NONE,
			'BUTTON_CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CB_BUTTON_CAPTION'),
			'FIELDS' => array(
				array(
					'TYPE' => 'phone',
					'CODE' => 'LEAD_PHONE',
					'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_PHONE'),
					'SORT' => 100,
					'REQUIRED' => 'Y',
					'MULTIPLE' => 'N',
					'PLACEHOLDER' => '',
				)
			)
		);

		return $callback;
	}

	public static function installVersion2()
	{
		$formDb = Internals\FormTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'IS_SYSTEM' => 'Y'
			),
		));
		while($form = $formDb->fetch())
		{
			Form::activate($form['ID'], true, self::getCurrentUserId());
		}
	}
}
