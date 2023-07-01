<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Crm\WebForm\Options;

use Bitrix\Main\Loader;
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
	private const SHOW_MORE_PAYMENT_SLIDER_PATH = '/bitrix/components/bitrix/salescenter.paysystem.panel/slider.php?type=main&mode=main&IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER';

	/** @var static $instance Instance. */
	private static $instance;

	/**
	 * Get instance.
	 *
	 * @return self
	 */
	public static function instance(): self
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
	public function toArray(): array
	{
		return [
			'languages' => $this->getLanguages(),
			'views' => $this->getViews(),
			'catalog' => [
				'id' => \CAllCrmCatalog::EnsureDefaultExists(),
				'currencies' => $this->getCurrencies(),
			],
			'payment' => $this->getPayment(),
			'document' => $this->getDocument(),
			'callback' => $this->getCallback(),
			'whatsapp' => $this->getWhatsApp(),
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
			],
			'contentTypes' => [
				['id' => 'image/*', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_FIELD_FILE_CONTENT_TYPE_IMAGE')],
				[
					'id' => 'x-bx/doc',
					'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_FIELD_FILE_CONTENT_TYPE_DOC_MSGVER_1'),
					'hint' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_FIELD_FILE_CONTENT_TYPE_DOC_HINT'),
				],
				[
					'id' => 'x-bx/arc',
					'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_FIELD_FILE_CONTENT_TYPE_ARCHIVE'),
					'hint' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_FIELD_FILE_CONTENT_TYPE_ARCHIVE_HINT'),
				],
				['id' => 'audio/*', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_FIELD_FILE_CONTENT_TYPE_AUDIO')],
				['id' => 'video/*', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_FIELD_FILE_CONTENT_TYPE_VIDEO')],
			],
			'integration' => $this->getIntegration(),
			'scenarios' => Main\DI\ServiceLocator::getInstance()->get('crm.service.webform.scenario')->getScenarioList(),
			'scenarioCategories' => Main\DI\ServiceLocator::getInstance()->get('crm.service.webform.scenario')->getScenarioCategoryList(),
			'sidebarButtons' => Main\DI\ServiceLocator::getInstance()->get('crm.service.webform.scenario')->getSidebarMenuItems(),
		];
	}

	/**
	 * Get restriction.
	 *
	 * @return array
	 */
	public function getRestriction(): array
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
	public function getLanguages(): array
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
	public function getCurrencies(): array
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
	public function getViews(): array
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
				['id' => 'bottom', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_VERT_BOTTOM')],
				['id' => 'top', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_VERT_TOP')],
			],

			'button' => [
				'fonts' => [
					['id' => 'modern', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_FONT_MODERN')],
					['id' => 'classic', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_FONT_CLASSIC')],
					['id' => 'elegant', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_FONT_ELEGANT')],
				],
				'aligns' => [
					['id' => 'inline', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_POS_INLINE')],
					['id' => 'left', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_POS_LEFT')],
					['id' => 'center', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_POS_CENTER')],
					['id' => 'right', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_POS_RIGHT')],
				],
				'plains' => [
					['id' => '0', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_BUTTON_TYPE_BUTTON')],
					['id' => '1', 'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_VIEW_BUTTON_TYPE_LINK')],
				],
			],
		];
	}

	/**
	 * Get payment.
	 *
	 * @return array
	 */
	public function getPayment(): array
	{
		return [
			'enabled' => WebForm\Manager::isOrdersAvailable(),
			'payers' => [],
			'moreSystemSliderPath' => static::SHOW_MORE_PAYMENT_SLIDER_PATH,
		];
	}

	/**
	 * Get document.
	 *
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public function getDocument(): array
	{
		$schemes = [];
		foreach (WebForm\Entity::getSchemes() as $schemeId => $scheme)
		{
			foreach ($scheme as $key => $value)
			{
				unset($scheme[$key]);
				$key = lcfirst(Main\Text\StringHelper::snake2camel($key));
				$scheme[$key] = $value;
			}

			if ($scheme['mainEntity'] === \CCrmOwnerType::SmartDocument)
			{
				continue;
			}

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

		$dynamic = [];
		$typesMap = Crm\Service\Container::getInstance()->getDynamicTypesMap();
		$typesMap->load([
			'isLoadCategories' => true,
			'isLoadStages' => true,
		]);
		foreach ($typesMap->getTypesCollection() as $type)
		{
			if (
				in_array($type->getEntityTypeId(), [\CCrmOwnerType::SmartInvoice])
			)
			{
				continue;
			}

			$categories = [];
			foreach ($typesMap->getCategories($type->getEntityTypeId()) as $category)
			{
				$stages = [];
				foreach ($typesMap->getStages($type->getEntityTypeId(), (int)$category->getId()) as $stage)
				{
					$stages[] = [
						'id' => $stage->getStatusId(),
						'name' => $stage->getName(),
					];
				}

				$categories[] = [
					'id' => (int)$category->getId(),
					'name' => $category->getName(),
					'stages' => $stages,
				];
			}

			if ((int)$type->getEntityTypeId() === \CCrmOwnerType::SmartDocument)
			{
				continue;
			}

			$dynamic[] = [
				'id' => (int)$type->getEntityTypeId(),
				'name' => $type->getTitle(),
				'categories' => $categories,
			];
		}

		return [
			'schemes' => $schemes,
			'duplicateModes' => $modes,
			'deal' => [
				'categories' => $dealCategories
			],
			'lead' => [
				'enabled' => Crm\Settings\LeadSettings::getCurrent()->isEnabled()
			],
			'dynamic' => $dynamic,
		];
	}

	/**
	 * Get callback dict.
	 *
	 * @return array
	 */
	public function getCallback(): array
	{
		$isEnabled = WebForm\Callback::canUse();
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
	 * Get whatsapp dict.
	 *
	 * @return array
	 */
	public function getWhatsApp(): array
	{
		return [
			'enabled' => WebForm\WhatsApp::canUse(),
			'setup' => [
				'completed' => WebForm\WhatsApp::isSetupCompleted(),
				'link' => WebForm\WhatsApp::getSetupLink(),
			],
			'messages' => WebForm\WhatsApp::getMessages(),
			'loc' => [
				'disabled' => 'Module not installed',
			],
			'help' => WebForm\WhatsApp::getHelpId(),
		];
	}


	/**
	 * Get captcha.
	 *
	 * @return array
	 */
	public function getCaptcha(): array
	{
		$hasOwn = WebForm\ReCaptcha::getKey(2) && WebForm\ReCaptcha::getSecret(2);
		$hasDefaults = WebForm\ReCaptcha::getDefaultKey(2) && WebForm\ReCaptcha::getDefaultSecret(2);
		return [
			'hasKeys' => $hasDefaults || $hasOwn,
		];
	}

	/**
	 * Get sign.
	 *
	 * @return array
	 */
	public function getSign(): array
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
	public function getPersonalization(): array
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
	public function getProperties(): array
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
	public function getDeps(): array
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
						'excludeFieldTypes' => [
							WebForm\Internals\FieldTable::TYPE_ENUM_BOOL
						],
					],
					[
						'id' => '!=',
						'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_COND_OP_NOTEQUAL'),
						'fieldTypes' => [],
						'excludeFieldTypes' => [
							WebForm\Internals\FieldTable::TYPE_ENUM_BOOL
						],
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
						'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_COND_OP_EMPTY1'),
						'fieldTypes' => [],
					],
					[
						'id' => 'any',
						'name' => Loc::getMessage('CRM_WEBFORM_OPTIONS_DICT_COND_OP_ANY1'),
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

	private function changeArrayKeysToCamelCase(array $array) : array
	{
		$new = [];
		foreach ($array as $key => $item)
		{
			if (is_array($item))
			{
				$item = $this->changeArrayKeysToCamelCase($item);
			}

			if (is_string($key))
			{
				$key = mb_strtolower($key);
				$key = ucwords($key,"_");
				$key = str_replace("_","",$key);
				$key = lcfirst($key);
			}

			$new[$key] = $item;
		}

		return $new;
	}

	public function getIntegration(): array
	{
		$providers = array_values(Crm\Ads\AdsForm::getProviders());

		return [
			'canUse' => Crm\Ads\AdsForm::canUse(),
			'directions' => [
				["code" => "export", "id" => Crm\Ads\Internals\AdsFormLinkTable::LINK_DIRECTION_EXPORT],
				["code" => "import", "id" => Crm\Ads\Internals\AdsFormLinkTable::LINK_DIRECTION_IMPORT],
			],
			'providers' => $this->changeArrayKeysToCamelCase($providers),
		];
	}
}
