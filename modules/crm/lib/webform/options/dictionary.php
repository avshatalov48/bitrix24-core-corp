<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Crm\WebForm\Options;

use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\SiteButton;
use Bitrix\Crm\WebForm;

Loc::loadMessages(__FILE__);

/**
 * Class Dictionary
 * @package Bitrix\Crm\WebForm\Options
 */
class Dictionary
{
	/** @var static $instance Instance. */
	private static $instance;

	/**
	 * Get instance.
	 *
	 * @return static
	 */
	public static function instance()
	{
		if (!self::$instance)
		{
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Get dictionary as array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return [
			'languages' => $this->getLanguages(),
			'views' => $this->getViews(),
			'currencies' => $this->getCurrencies(),
			'payment' => $this->getPayment(),
			'document' => $this->getDocument(),
			'callback' => $this->getCallback(),
			'captcha' => $this->getCaptcha(),
			'templates' => [],
			'personalization' => $this->getPersonalization(),
			'properties' => $this->getProperties(),
			'deps' => $this->getDeps(),
			'sign' => $this->getSign(),
			'restriction' => $this->getRestriction(),
			'product' => [
				'isCloud' => Crm\Integration\Bitrix24\Product::isCloud(),
				'isRegionRussian' => Crm\Integration\Bitrix24\Product::isRegionRussian(),
			]
		];
	}

	/**
	 * Get restriction.
	 *
	 * @return array
	 */
	public function getRestriction()
	{
		return [
			'helper' => Crm\Restriction\RestrictionManager::getWebformRestriction()->prepareInfoHelperScript()
		];
	}

	/**
	 * Get languages.
	 *
	 * @return array
	 */
	public function getLanguages()
	{
		$languages = [];
		foreach (SiteButton\Manager::getLanguages() as $languageId => $language)
		{
			$languages[] = ['id' => $languageId, 'name' => $language['NAME']];
		}

		return $languages;
	}

	/**
	 * Get currencies.
	 *
	 * @return array
	 */
	public function getCurrencies()
	{
		$currency = \CCrmCurrency::GetCurrencyFormatParams(\CCrmCurrency::GetBaseCurrencyID());
		return [
			[
				'code' => $currency['CURRENCY'],
				'title' => $currency['FULL_NAME'],
				'format' => $currency['FORMAT_STRING'],
			]
		];
	}

	/**
	 * Get views.
	 *
	 * @return array
	 */
	public function getViews()
	{
		return [
			'types' => [
				['id' => 'popup', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_TYPE_POPUP')],
				['id' => 'panel', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_TYPE_PANEL')],
			],
			'positions' => [
				['id' => 'left', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_POS_LEFT')],
				['id' => 'center', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_POS_CENTER')],
				['id' => 'right', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_POS_RIGHT')],
			],
			'verticals' => [
				['id' => 'bottom', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_VERT_TOP')],
				['id' => 'top', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_VERT_BOTTOM')],
			],
		];
	}

	/**
	 * Get payment.
	 *
	 * @return array
	 */
	public function getPayment()
	{
		return [
			'enabled' => WebForm\Manager::isOrdersAvailable(),
			'payers' => [],
			'systems' => [],
		];
	}

	/**
	 * Get document.
	 *
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public function getDocument()
	{
		$schemes = [];
		foreach (WebForm\Entity::getSchemes() as $schemeId => $scheme)
		{
			$scheme = array_change_key_case($scheme);
			$scheme['id'] = $schemeId;
			$schemes[] = $scheme;
		}

		$modes = [];
		foreach (WebForm\ResultEntity::getDuplicateModeList() as $modeKey => $modeName)
		{
			$modes[] = ['id' => $modeKey, 'name' => $modeName];
		}

		$dealCategories = [];
		foreach (Crm\Category\DealCategory::getAll(true) as $category)
		{
			$dealCategories[] = ['id' => $category['ID'], 'name' => $category['NAME']];
		}

		return [
			'schemes' => $schemes,
			'duplicateModes' => $modes,
			'deal' => [
				'categories' => $dealCategories
			],
			'lead' => [
				'enabled' => LeadSettings::getCurrent()->isEnabled()
			],
		];
	}

	/**
	 * Get callback dict.
	 *
	 * @return array
	 * @throws Main\LoaderException
	 */
	public function getCallback()
	{
		$isEnabled = Main\Loader::includeModule('voximplant');
		$numbers = [];
		if ($isEnabled)
		{
			foreach (WebForm\Callback::getPhoneNumbers() as $number)
			{
				$numbers[] = ['id' => $number['CODE'], 'name' => $number['NAME']];
			}
		}

		return [
			'enabled' => $isEnabled,
			'from' => $numbers
		];
	}

	/**
	 * Get captcha.
	 *
	 * @return array
	 */
	public function getCaptcha()
	{
		return [
			'hasDefaults' => WebForm\ReCaptcha::getDefaultKey(2) && WebForm\ReCaptcha::getDefaultSecret(2),
		];
	}

	/**
	 * Get sign.
	 *
	 * @return array
	 */
	public function getSign()
	{
		return [
			'canRemove' => WebForm\Form::canRemoveCopyright(),
		];
	}

	/**
	 * Get personalization.
	 *
	 * @return array
	 */
	public function getPersonalization()
	{
		return [
			'list' => [
				['id' => '{{name}}', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PERC_FIELD_NAME')],
				['id' => '{{last-name}}', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PERC_FIELD_LASTNAME')],
				['id' => '{{company-name}}', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PERC_FIELD_COMPANY_NAME')],
				['id' => '{{second-name}}', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PERC_FIELD_SECOND_NAME')],
				['id' => '{{phone}}', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PERC_FIELD_PHONE')],
				['id' => '{{email}}', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PERC_FIELD_EMAIL')],
			],
		];
	}

	/**
	 * Get properties.
	 *
	 * @return array
	 */
	public function getProperties()
	{
		return [
			'list' => [
				[
					'id' => '%from_domain%',
					'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PROPS_DOMAIN'),
					'desc' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PROPS_DOMAIN_DESC'),
				],
				[
					'id' => '%from_url%',
					'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PROPS_URL'),
					'desc' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PROPS_URL_DESC'),
				],
				[
					'id' => '%my_param1%',
					'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PROPS_PARAM'),
					'desc' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PROPS_PARAM_DESC'),
				],
				[
					'id' => '%crm_result_id%',
					'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PROPS_RESULT_ID'),
					'desc' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PROPS_RESULT_ID_DESC'),
				],
				[
					'id' => '%crm_form_id%',
					'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PROPS_FORM_ID'),
					'desc' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PROPS_FORM_ID_DESC'),
				],
				[
					'id' => '%crm_form_name%',
					'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PROPS_FORM_NAME'),
					'desc' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_PROPS_FORM_NAME_DESC'),
				],
			],
		];
	}

	/**
	 * Get depending.
	 *
	 * @return array
	 */
	public function getDeps()
	{
		$groupTypes = [];
		foreach (WebForm\Internals\FieldDepGroupTable::getDepGroupTypes() as $groupTypeId => $groupTypeName)
		{
			$groupTypes[] = ['id' => $groupTypeId, 'name' => $groupTypeName];
		}

		$stringTypes = [
			WebForm\Internals\FieldTable::TYPE_ENUM_EMAIL,
			WebForm\Internals\FieldTable::TYPE_ENUM_PHONE,
			WebForm\Internals\FieldTable::TYPE_ENUM_STRING,
			WebForm\Internals\FieldTable::TYPE_ENUM_TEXT,
			WebForm\Internals\FieldTable::TYPE_ENUM_TYPED_STRING,
			'name',
			'last-name',
			'second-name',
			'company-name',
		];

		$numberTypes = [
			WebForm\Internals\FieldTable::TYPE_ENUM_FLOAT,
			WebForm\Internals\FieldTable::TYPE_ENUM_INT,
			WebForm\Internals\FieldTable::TYPE_ENUM_MONEY,
		];

		return [
			'group' => [
				'types' => $groupTypes
			],
			'field' => [
				'types' => [],
				'disallowed' => [
					WebForm\Internals\FieldTable::TYPE_ENUM_BR,
					WebForm\Internals\FieldTable::TYPE_ENUM_HR,
					WebForm\Internals\FieldTable::TYPE_ENUM_RESOURCEBOOKING,
					WebForm\Internals\FieldTable::TYPE_ENUM_PAGE,
				],
			],
			'condition' => [
				'events' => [
					['id' => 'change', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_COND_EVENT_CHANGE')],
				],
				'operations' => [
					[
						'id' => '=',
						'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_COND_OP_EQUAL'),
						'fieldTypes' => [],
					],
					[
						'id' => '!=',
						'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_COND_OP_NOTEQUAL'),
						'fieldTypes' => [],
					],
					[
						'id' => '>',
						'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_COND_OP_GREATER'),
						'fieldTypes' => $numberTypes,
					],
					[
						'id' => '>=',
						'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_COND_OP_GREATEROREQUAL'),
						'fieldTypes' => $numberTypes,
					],
					[
						'id' => '<',
						'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_COND_OP_LESS'),
						'fieldTypes' => $numberTypes,
					],
					[
						'id' => '<=',
						'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_COND_OP_LESSOREQUAL'),
						'fieldTypes' => $numberTypes,
					],
					[
						'id' => 'empty',
						'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_COND_OP_EMPTY'),
						'fieldTypes' => [],
					],
					[
						'id' => 'any',
						'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_COND_OP_ANY'),
						'fieldTypes' => [],
					],
					[
						'id' => 'contain',
						'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_COND_OP_CONTAIN'),
						'fieldTypes' => $stringTypes,
					],
					[
						'id' => '!contain',
						'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_COND_OP_NOTCONTAIN'),
						'fieldTypes' => $stringTypes,
					],
				],
			],

			'action' => [
				'types' => [
					['id' => 'show', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_COND_ACTION_SHOW')],
					['id' => 'hide', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_COND_ACTION_HIDE')],
				]
			]
		];
	}
}
