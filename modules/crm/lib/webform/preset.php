<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Crm\Integration\UserConsent;

Loc::loadMessages(__FILE__);

class Preset
{
	protected $errors = [];
	protected static $version = 2;
	protected static $versionOptionName = 'webform_preset_version';

	protected static function getVersion(): int
	{
		return self::$version;
	}

	protected static function getInstalledVersion(): int
	{
		return (int) Option::get('crm', self::$versionOptionName, 0);
	}

	public static function updateInstalledVersion($version = null): void
	{
		if($version === null)
		{
			$version = self::getVersion();
		}

		Option::set('crm', self::$versionOptionName, $version);
	}

	public static function checkVersion(): bool
	{
		return self::getVersion() > self::getInstalledVersion();
	}

	public function getErrors(): array
	{
		return $this->errors;
	}

	public function hasErrors(): bool
	{
		return count($this->errors) > 0;
	}

	public function isInstalled($xmlId): ?bool
	{
		$formDb = Internals\FormTable::getList([
			'select' => ['ID'],
			'filter' => ['=IS_SYSTEM' => 'Y', '=XML_ID' => $xmlId],
		]);
		return $formDb->fetch() ? true : false;
	}

	public function install(): bool
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

		$callback = [__CLASS__, 'installVersion' . self::getVersion()];
		if (is_callable($callback))
		{
			call_user_func_array($callback, []);
		}

		return $this->hasErrors();
	}

	public function uninstall($xmlId = null)
	{
		$filter = ['=IS_SYSTEM' => 'Y'];
		if($xmlId)
		{
			$filter['=XML_ID'] = $xmlId;
		}
		$formDb = Internals\FormTable::getList([
			'select' => ['ID'],
			'filter' => $filter,
		]);
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

	protected function addForm($formData): bool
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

	public static function getById($xmlId): ?array
	{
		$presets = static::getList();
		foreach($presets as $preset)
		{
			if($preset['XML_ID'] == $xmlId)
			{
				return $preset;
			}
		}

		return null;
	}

	private static function isLeadEnabled(): bool
	{
		return LeadSettings::getCurrent()->isEnabled();
	}

	public static function getList(): array
	{
		$list = [
			[
				'XML_ID' => 'crm_preset_cd', //cd - ContactData
				'NAME' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CD_NAME'),
				'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CD_CAPTION'),
				'DESCRIPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CD_DESCRIPTION'),
				'RESULT_SUCCESS_TEXT' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_RESULT_SUCCESS_TEXT'),
				'RESULT_FAILURE_TEXT' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_RESULT_FAILURE_TEXT'),
				'ENTITY_SCHEME' => (string) (self::isLeadEnabled() ? Entity::ENUM_ENTITY_SCHEME_LEAD : Entity::ENUM_ENTITY_SCHEME_DEAL),
				//'TEMPLATE_ID' => Helper::ENUM_TEMPLATE_LIGHT,
				'COPYRIGHT_REMOVED' => 'N',
				'IS_PAY' => 'N',
				'DUPLICATE_MODE' => ResultEntity::DUPLICATE_CONTROL_MODE_MERGE,
				'FORM_SETTINGS' => [
					'DEAL_DC_ENABLED' => 'Y',
				],
				'BUTTON_CAPTION' => '',
				'FIELDS' => [
					[
						'TYPE' => 'string',
						'CODE' => self::isLeadEnabled() ? 'LEAD_NAME' : 'CONTACT_NAME',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_NAME'),
						'SORT' => 100,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					],
					[
						'TYPE' => 'string',
						'CODE' => self::isLeadEnabled() ? 'LEAD_LAST_NAME' : 'CONTACT_LAST_NAME',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_LAST_NAME'),
						'SORT' => 200,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					],
					[
						'TYPE' => 'phone',
						'CODE' => self::isLeadEnabled() ? 'LEAD_PHONE' : 'CONTACT_PHONE',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_PHONE'),
						'SORT' => 300,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					],
					[
						'TYPE' => 'email',
						'CODE' => self::isLeadEnabled() ? 'LEAD_EMAIL' : 'CONTACT_EMAIL',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_EMAIL'),
						'SORT' => 400,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					]
				]
			],
			[
				'XML_ID' => 'crm_preset_fb', //fb - FeedBack
				'NAME' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_FB_NAME'),
				'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_FB_CAPTION'),
				'DESCRIPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_FB_DESCRIPTION'),
				'RESULT_SUCCESS_TEXT' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_RESULT_SUCCESS_TEXT'),
				'RESULT_FAILURE_TEXT' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_RESULT_FAILURE_TEXT'),
				'ENTITY_SCHEME' => (string) (self::isLeadEnabled() ? Entity::ENUM_ENTITY_SCHEME_LEAD : Entity::ENUM_ENTITY_SCHEME_DEAL),
				//'TEMPLATE_ID' => Helper::ENUM_TEMPLATE_LIGHT,
				'COPYRIGHT_REMOVED' => 'N',
				'IS_PAY' => 'N',
				'DUPLICATE_MODE' => ResultEntity::DUPLICATE_CONTROL_MODE_MERGE,
				'FORM_SETTINGS' => [
					'DEAL_DC_ENABLED' => 'Y',
				],
				'BUTTON_CAPTION' => '',
				'FIELDS' => [
					[
						'TYPE' => 'string',
						'CODE' => self::isLeadEnabled() ? 'LEAD_NAME' : 'CONTACT_NAME',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_NAME'),
						'SORT' => 100,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					],
					[
						'TYPE' => 'string',
						'CODE' => self::isLeadEnabled() ? 'LEAD_LAST_NAME' : 'CONTACT_LAST_NAME',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_LAST_NAME'),
						'SORT' => 200,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					],
					[
						'TYPE' => 'phone',
						'CODE' => self::isLeadEnabled() ? 'LEAD_PHONE' : 'CONTACT_PHONE',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_PHONE'),
						'SORT' => 300,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					],
					[
						'TYPE' => 'email',
						'CODE' => self::isLeadEnabled() ? 'LEAD_EMAIL' : 'CONTACT_EMAIL',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_EMAIL'),
						'SORT' => 400,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					],
					[
						'TYPE' => 'text',
						'CODE' => self::isLeadEnabled() ? 'LEAD_COMMENTS' : 'DEAL_COMMENTS',
						'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_COMMENTS'),
						'SORT' => 500,
						'REQUIRED' => 'N',
						'MULTIPLE' => 'N',
						'PLACEHOLDER' => '',
					]
				]
			]
		];

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

	public static function getCallback($phoneCode, $phoneCaption): array
	{
		if (!$phoneCode && Loader::includeModule('voximplant'))
		{
			$callbackNumbers = Callback::getPhoneNumbers();
			if ($callbackNumbers)
			{
				$phoneCode = $callbackNumbers[0]['CODE'];
				$phoneCaption = $callbackNumbers[0]['NAME'];
			}
		}

		$callback = [
			'XML_ID' => 'crm_preset_cb_' . $phoneCode, //cb - CallBack
			'NAME' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CB_NAME', ['#call_from#' => $phoneCaption]),
			'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CB_CAPTION'),
			'DESCRIPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CB_DESCRIPTION'),
			'RESULT_SUCCESS_TEXT' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CB_RESULT_SUCCESS_TEXT'),
			'RESULT_FAILURE_TEXT' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_RESULT_FAILURE_TEXT'),
			'ENTITY_SCHEME' => (string) (self::isLeadEnabled() ? Entity::ENUM_ENTITY_SCHEME_LEAD : Entity::ENUM_ENTITY_SCHEME_DEAL),
			//'TEMPLATE_ID' => Helper::ENUM_TEMPLATE_LIGHT,
			'COPYRIGHT_REMOVED' => 'N',
			'IS_PAY' => 'N',
			'IS_CALLBACK_FORM' => 'Y',
			'CALL_FROM' => $phoneCode,
			'CALL_TEXT' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CB_CALL_TEXT'),
			'DUPLICATE_MODE' => ResultEntity::DUPLICATE_CONTROL_MODE_MERGE,
			'FORM_SETTINGS' => [
				'DEAL_DC_ENABLED' => 'Y',
			],
			'BUTTON_CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_CB_BUTTON_CAPTION'),
			'FIELDS' => [
				[
					'TYPE' => 'phone',
					'CODE' => self::isLeadEnabled() ? 'LEAD_PHONE' : 'CONTACT_PHONE',
					'CAPTION' => Loc::getMessage('CRM_WEBFORM_PRESET_ITEM_DEF_FIELD_PHONE'),
					'SORT' => 100,
					'REQUIRED' => 'Y',
					'MULTIPLE' => 'N',
					'PLACEHOLDER' => '',
				]
			]
		];

		return $callback;
	}

	public static function installVersion2()
	{
		$formDb = Internals\FormTable::getList([
			'select' => ['ID'],
			'filter' => [
				'IS_SYSTEM' => 'Y'
			],
		]);
		while($form = $formDb->fetch())
		{
			Form::activate($form['ID'], true, self::getCurrentUserId());
		}
	}
}
