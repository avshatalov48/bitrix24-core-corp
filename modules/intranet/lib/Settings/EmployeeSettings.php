<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Intranet\Settings\Controls\Section;
use Bitrix\Intranet\Settings\Controls\Selector;
use Bitrix\Intranet\Settings\Controls\Switcher;
use Bitrix\Intranet\Settings\Search\SearchEngine;
use Bitrix\Location\Infrastructure\FormatCode;
use Bitrix\Location\Service\FormatService;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\PhoneNumber\MetadataProvider;
use Bitrix\Main\PhoneNumber\Parser;
use Bitrix\Main\PhoneNumber\Format;
use Bitrix\Main\Result;
use Bitrix\Socialservices\Network;
use Bitrix\Intranet\Integration\Main\Culture;

class EmployeeSettings extends AbstractSettings
{
	public const TYPE = 'employee';

	private bool $isBitrix24;
	private bool $isExtranetInstalled;

	public function __construct(array $data = [])
	{
		parent::__construct($data);
		$this->isBitrix24 = IsModuleInstalled("bitrix24");
		$this->isExtranetInstalled = IsModuleInstalled("extranet");
	}

	public function validate(): ErrorCollection
	{
		$errors = new ErrorCollection();

		if (isset($this->data["FORMAT_NAME"]))
		{
			if (!preg_match('/^(?:#TITLE#|#NAME#|#LAST_NAME#|#SECOND_NAME#|#NAME_SHORT#|#LAST_NAME_SHORT#|#SECOND_NAME_SHORT#|#EMAIL#|#ID#|\s|,)+$/D', $this->data["FORMAT_NAME"]))
			{
				$errors->setError(
					new Error(
						Loc::getMessage('SETTINGS_FORMAT_NAME_ERROR'),
						0,
						[
							'page' => $this->getType(),
							'field' => 'FORMAT_NAME',
						]
					)
				);
			}
		}

		return $errors;
	}

	public function save(): Result
	{
		if (isset($this->data["FORMAT_NAME"]))
		{
			$culture["FORMAT_NAME"] = $this->data["FORMAT_NAME"];
			Culture::updateCulture($culture);

			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->ClearByTag('sonet_group');
			}
		}

		if (isset($this->data['address_format_code']) && Loader::includeModule('location'))
		{
			FormatCode::setCurrent($this->data['address_format_code']);
		}

		if (isset($this->data['phone_number_default_country']) && $this->data['phone_number_default_country'] > 0)
		{
			\COption::SetOptionInt('main', 'phone_number_default_country', $this->data['phone_number_default_country']);
		}

		if (isset($this->data['show_fired_employees']) && $this->data['show_fired_employees'] === 'Y')
		{
			\COption::SetOptionString("bitrix24", "show_fired_employees", "Y");
		}
		else
		{
			\COption::SetOptionString("bitrix24", "show_fired_employees", "N");
		}

		if ($this->isBitrix24)
		{
			if (Loader::includeModule("socialservices"))
			{
				Network::setRegisterSettings(array(
					"REGISTER" => isset($this->data['allow_register']) && $this->data['allow_register'] === 'Y' ? 'Y' : 'N',
				));
			}

			if (isset($this->data['allow_invite_users']) && $this->data['allow_invite_users'] === 'Y')
			{
				\COption::SetOptionString("bitrix24", "allow_invite_users", "Y");
			}
			else
			{
				\COption::SetOptionString("bitrix24", "allow_invite_users", "N");
			}

			if (isset($this->data["show_year_for_female"]) && $this->data["show_year_for_female"]  === 'Y')
			{
				\COption::SetOptionString("intranet", "show_year_for_female", "Y", false);
			}
			else
			{
				\COption::SetOptionString("intranet", "show_year_for_female", "N", false);
			}

			if (isset($this->data['feature_extranet']) && $this->data['feature_extranet'] === 'Y')
			{
				Option::set('bitrix24', 'feature_extranet', 'Y');
				\CUserOptions::DeleteOptionsByName('intranet', 'isUserListPresetsUpdated');
				\CBitrix24::updateExtranetUsersActivity(true);
				if (!$this->isExtranetInstalled)
				{
					ModuleManager::add("extranet");
				}
				$manualModulesChangedList['extranet'] = 'Y';
			}
			elseif (
				isset($this->data['feature_extranet'])
				&& $this->data['feature_extranet'] !== 'Y'
				&& $this->isExtranetInstalled
			)
			{
				Option::set('bitrix24', 'feature_extranet', 'N');
				\CUserOptions::DeleteOptionsByName('intranet', 'isUserListPresetsUpdated');
				\CBitrix24::updateExtranetUsersActivity(false);
				if (\Bitrix\Extranet\PortalSettings::getInstance()->canBeDeleted())
				{
					ModuleManager::delete('extranet');
					$manualModulesChangedList['extranet'] = 'N';
				}
			}
			if (!empty($manualModulesChangedList))
			{
				$event = new Event("bitrix24", "OnManualModuleAddDelete", [
					'modulesList' => $manualModulesChangedList,
				]);
				$event->send();
			}
		}

		if ($this->data['general_chat_message_join'] <> 'N')
		{
			Option::set('im', 'general_chat_message_join', true);
		}
		else
		{
			Option::set('im', 'general_chat_message_join', false);
		}

		if ($this->data['allow_new_user_lf'] <> 'N')
		{
			Option::set('intranet', 'BLOCK_NEW_USER_LF_SITE', 'N', false, SITE_ID);
		}
		else
		{
			Option::set('intranet', 'BLOCK_NEW_USER_LF_SITE', 'Y', false, SITE_ID);
		}

		$allowCompanyPulseValue = Option::get('intranet', 'allow_company_pulse', 'Y');
		if ($this->data['allow_company_pulse'] === 'N' && $allowCompanyPulseValue === 'Y')
		{
			Option::set('intranet', 'allow_company_pulse', "N");

			\Bitrix\Intranet\UStat\UStat::disableEventHandler();
		}
		else if ($this->data['allow_company_pulse'] === 'Y' && $allowCompanyPulseValue === 'N')
		{
			Option::set('intranet', 'allow_company_pulse', "Y");
			Option::set('intranet', 'check_limit_company_pulse', "N");

			\Bitrix\Intranet\UStat\UStat::enableEventHandler();
		}

		return new Result();
	}

	public function get(): SettingsInterface
	{
		$data = [];
		if ($this->isBitrix24)
		{
			$data['allow_register'] = new Switcher(
				'settings-employee-field-allow_register',
				'allow_register',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_FAST_REG'),
				'N',
				[
					'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_FAST_REQ_ON_MSGVER_1'),
				],
				helpDesk: 'redirect=detail&code=17726876',
			);
			if(Loader::includeModule("socialservices"))
			{
				$registerSettings = Network::getRegisterSettings();
				$data['allow_register']->setValue($registerSettings['REGISTER'] == 'Y' ? 'Y' : 'N');
			}

			$data['allow_invite_users'] = new Switcher(
				'settings-employee-field-allow_invite_users',
				'allow_invite_users',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_USERS_TO_INVITE'),
				Option::get('bitrix24', 'allow_invite_users', 'N'),
				[
					'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_USERS_TO_INVITE_ON'),
				],
			);

			$culture = Application::getInstance()->getContext()->getCulture();

			$data['show_year_for_female'] = new Switcher(
				'settings-employee-field-show_year_for_female',
				'show_year_for_female',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_BIRTH_YEAR'),
				Option::get('intranet', 'show_year_for_female', 'N'),
				[
					'on' => FormatDate($culture->get('LONG_DATE_FORMAT')),
					'off' => FormatDate($culture->get('DAY_MONTH_FORMAT')),
					'hintTitle' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_TITLE_BIRTH_YEAR')
				],
			);

			if (!$this->isExtranetInstalled || \Bitrix\Extranet\PortalSettings::getInstance()->isModuleToggleable())
			{
				$defaultExtranet = $this->isExtranetInstalled ? 'Y' : 'N';
				$data['feature_extranet'] = new Switcher(
					'settings-employee-field-feature_extranet',
					'feature_extranet',
					Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_EXTRANET'),
					Option::get('bitrix24', 'feature_extranet', $defaultExtranet),
					[
						'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_EXTRANET_ON_MSGVER_1'),
					],
					helpDesk: 'redirect=detail&code=17983050'
				);
			}
		}

		$data['show_fired_employees'] = new Switcher(
			'settings-employee-field-show_fired_employees',
			'show_fired_employees',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_QUIT_EMPLOYEE'),
			Option::get('bitrix24', 'show_fired_employees', 'Y'),
			[
				'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_QUIT_EMPLOYEE_ON'),
			]
		);

		$data['general_chat_message_join'] = new Switcher(
			'settings-employee-field-general_chat_message_join',
			'general_chat_message_join',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_MESSAGE_NEW_EMPLOYEE'),
			Option::get('im', 'general_chat_message_join') ? 'Y' : 'N',
			[
				'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_MESSAGE_NEW_EMPLOYEE_ON'),
			]
		);

		$data['allow_new_user_lf'] = new Switcher(
			'settings-employee-field-allow_new_user_lf',
			'allow_new_user_lf',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_MESSAGE_NEW_EMPLOYEE_LF'),
			Option::get('intranet', 'BLOCK_NEW_USER_LF_SITE', 'N', SITE_ID) === 'Y' ? 'N' : 'Y',
			[
				'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_MESSAGE_NEW_EMPLOYEE_LF_ON'),
			]
		);

		$allowCompanyPulseValue = Option::get('intranet', 'allow_company_pulse', null);
		if (is_null($allowCompanyPulseValue))
		{
			Option::set('intranet', 'allow_company_pulse', "Y");
			$allowCompanyPulseValue = "Y";
		}
		$url = "";
		if (Loader::includeModule('ui'))
		{
			$url = \Bitrix\UI\Util::getArticleUrlByCode('6474093');
		}

		$data['allow_company_pulse'] = new Switcher(
			'settings-employee-field-allow_company_pulse',
			'allow_company_pulse',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_MESSAGE_COMPANY_PULSE'),
			$allowCompanyPulseValue,
			[
				'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_WIDGET', [
					'[helpdesklink]' => '<a class=\'more\' href=\'' . $url . '\'>',
					'[/helpdesklink]' => '</a>'
				])
			],
		);

		$values = [];
		$currentSite = Culture::getCurrentSite();
		foreach (\CSite::GetNameTemplates() as $format => $name)
		{
			$values[] = [
				'value' => $format,
				'name' => $name,
				'selected' => $format === $currentSite["FORMAT_NAME"]
			];
		}

		$data["fieldFormatName"] = new Selector(
			'settings-employee-field-name_formats',
			'FORMAT_NAME',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_NAME_FORMAT'),
			$values,
			$currentSite["FORMAT_NAME"]
		);


		$countriesReference = GetCountryArray();
		$phoneNumberDefaultCountry = Parser::getDefaultCountry();
		$currentCountry = GetCountryIdByCode($phoneNumberDefaultCountry);
		$countries = [];
		foreach ($countriesReference['reference_id'] as $k => $v)
		{
			$countries[] =[
				'value' => $v,
				'name' => $countriesReference['reference'][$k],
				'selected' => $v === $currentCountry
			];
		}

		$phoneNumberHint = $this->getPhoneNumberFormatHint();
		$phoneNumberHint['hintTitle'] = Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_TITLE_NUMBER_FORMAT');
		$data['fieldFormatPhoneNumber'] = new Selector(
			'settings-employee-field-phone_number_default_country',
			'phone_number_default_country',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_COUNTRY_PHONE_NUMBER'),
			$countries,
			$currentCountry,
			hints: $phoneNumberHint
		);

		$data['SECTION_PROFILE'] = new Section(
			'settings-employee-section-profile',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_PROFILE'),
			'ui-icon-set --person'
		);

		$data['SECTION_INVITE'] = new Section(
			'settings-employee-section-invite',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_INVITE'),
			'ui-icon-set --person-plus',
			false
		);

		$data['SECTION_ADDITIONAL'] = new Section(
			'settings-employee-section-additional',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_ADDITIONAL'),
			'ui-icon-set --apps',
			false
		);

		if (Loader::includeModule('location'))
		{
			$addressFormatList = [];
			$hintList = [];
			$sanitizer = new \CBXSanitizer();
			$current = FormatCode::getCurrent();
			foreach(FormatService::getInstance()->findAll(LANGUAGE_ID) as $format)
			{
				$addressFormatList[] = [
					'value' => $format->getCode(),
					'name' => $format->getName(),
					'selected' => $current === $format->getCode()
				];
				$hintList[$format->getCode()] = $sanitizer->SanitizeHtml($format->getDescription());
			}
			usort($addressFormatList, function ($a, $b) {
				return strcmp($a['name'], $b['name']);
			});
			$hintList['hintTitle'] = Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_TITLE_ADDRESS_FORMAT');
			$data['fieldFormatAddress'] = new Selector(
				'settings-employee-field-format_address',
				'address_format_code',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ADDRESS_FORMAT'),
				$addressFormatList,
				FormatCode::getCurrent(),
				hints: $hintList
			);
		}
		$data['IS_BITRIX_24'] = $this->isBitrix24;

		return new static($data);
	}

	/**
	 * @return array
	 */
	public function getPhoneNumberFormatHint(): array
	{
		$phoneNumberHint = [];
		$metaCountry = MetadataProvider::getInstance()->toArray()['metadata'];
		foreach ($metaCountry as $code => $meta)
		{
			if (!isset($meta['mobile']['exampleNumber']) || (int)GetCountryIdByCode($code) <= 0)
			{
				continue;
			}
			$phoneNumber = Parser::getInstance()->parse($meta['mobile']['exampleNumber'], $code);
			$phoneNumberHint[GetCountryIdByCode($code)] = $phoneNumber->format(Format::INTERNATIONAL);
		}

		return $phoneNumberHint;
	}

	public function find(string $query): array
	{
		$index = [
			'settings-employee-section-profile' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_PROFILE'),
			'settings-employee-section-invite' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_INVITE'),
			'settings-employee-section-additional' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_ADDITIONAL'),
		];
		if ($this->isBitrix24)
		{
			$index['show_year_for_female'] = Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_BIRTH_YEAR');
			$index['allow_register'] = Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_FAST_REG');
			$index['allow_invite_users'] = Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_USERS_TO_INVITE');
			$index['feature_extranet'] = Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_EXTRANET');
		}
		if (Loader::includeModule('location'))
		{
			$index['address_format_code'] = Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ADDRESS_FORMAT');
		}

		$searchEngine = SearchEngine::initWithDefaultFormatter($index + [
			'phone_number_default_country' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_COUNTRY_PHONE_NUMBER'),
			'FORMAT_NAME_selector' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_NAME_FORMAT'),
			'show_fired_employees' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_QUIT_EMPLOYEE'),
			'general_chat_message_join' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_MESSAGE_NEW_EMPLOYEE'),
			'allow_new_user_lf' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_MESSAGE_NEW_EMPLOYEE_LF'),
			'allow_company_pulse' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_MESSAGE_COMPANY_PULSE'),
		]);

		return $searchEngine->find($query);
	}
}
