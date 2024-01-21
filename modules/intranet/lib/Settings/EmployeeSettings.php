<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Location\Infrastructure\FormatCode;
use Bitrix\Location\Service\FormatService;
use Bitrix\Main\Application;
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

	public function __construct(array $data = [])
	{
		parent::__construct($data);
		$this->isBitrix24 = IsModuleInstalled("bitrix24");
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

		if (isset($this->data["show_fired_employees"]) && $this->data["show_fired_employees"] === 'Y')
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
					"REGISTER" => isset($this->data["allow_register"]) && $this->data["allow_register"] === 'Y' ? "Y" : "N",
				));
			}

			if (isset($this->data["allow_invite_users"]) && $this->data["allow_invite_users"] === 'Y')
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


			if (isset($this->data["feature_extranet"]) && $this->data["feature_extranet"] === 'Y')
			{
				\COption::SetOptionString("bitrix24", "feature_extranet", "Y");
				\CBitrix24::updateExtranetUsersActivity(true);
				if (!IsModuleInstalled("extranet"))
				{
					ModuleManager::add("extranet");
				}
				$manualModulesChangedList['extranet'] = 'Y';
			}
			elseif (isset($this->data["feature_extranet"])
				&& $this->data["feature_extranet"] !== 'Y'
				&& IsModuleInstalled("extranet"))
			{
				\COption::SetOptionString("bitrix24", "feature_extranet", "N");
				\CBitrix24::updateExtranetUsersActivity(false);
				ModuleManager::delete("extranet");
				$manualModulesChangedList['extranet'] = 'N';
			}
			if (!empty($manualModulesChangedList))
			{
				$event = new Event("bitrix24", "OnManualModuleAddDelete", [
					'modulesList' => $manualModulesChangedList,
				]);
				$event->send();
			}
		}

		if ($this->data["general_chat_message_join"] <> 'N')
		{
			\COption::SetOptionString("im", "general_chat_message_join", true);
		}
		else
		{
			\COption::SetOptionString("im", "general_chat_message_join", false);
		}

		if ($this->data['allow_new_user_lf'] <> 'N')
		{
			\COption::SetOptionString('intranet', 'BLOCK_NEW_USER_LF_SITE', 'N', false, SITE_ID);
		}
		else
		{
			\COption::SetOptionString('intranet', 'BLOCK_NEW_USER_LF_SITE', 'Y', false, SITE_ID);
		}

		return new Result();
	}

	public function get(): SettingsInterface
	{
		$data = [];
		if ($this->isBitrix24)
		{
			$data["allow_register"] = "N";
			if(Loader::includeModule("socialservices"))
			{
				$registerSettings = Network::getRegisterSettings();
				$data["allow_register"] = $registerSettings["REGISTER"] == "Y" ? "Y" : "N";
			}
			$data["allow_invite_users"] = \COption::GetOptionString("bitrix24", "allow_invite_users", "N");

			$culture = Application::getInstance()->getContext()->getCulture();
			$data['show_year_for_female'] = [
				'current' => \COption::GetOptionString("intranet", "show_year_for_female", "N"),
				'hintOn' => FormatDate($culture->get('LONG_DATE_FORMAT')),
				'hintOff' => FormatDate($culture->get('DAY_MONTH_FORMAT')),
			];
			$defaultExtranet = IsModuleInstalled("extranet") ? 'Y' : 'N';
			$data['feature_extranet'] = \COption::GetOptionString("bitrix24", "feature_extranet", $defaultExtranet);
		}
		$data['show_fired_employees'] = \COption::GetOptionString("bitrix24", "show_fired_employees", "Y");
		$data['general_chat_message_join'] = \COption::GetOptionString("im", "general_chat_message_join") ? 'Y' : 'N';
		$data['allow_new_user_lf'] = \COption::GetOptionString("intranet", "BLOCK_NEW_USER_LF_SITE", "N", SITE_ID) === 'Y' ? 'N' : 'Y';

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

		$data["NAME_FORMATS"] = [
			'name' => 'FORMAT_NAME',
			'values' => $values,
			'current' => $currentSite["FORMAT_NAME"],
		];
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

		$data['PHONE_NUMBER_DEFAULT_COUNTRY'] = [
			'name' => 'phone_number_default_country',
			'values' => $countries,
			'current' => $currentCountry,
			'hints' => $phoneNumberHint,
		];
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

			$data['LOCATION_ADDRESS_FORMAT_LIST'] = [
				'name' => 'address_format_code',
				'values' => $addressFormatList,
				'current' => FormatCode::getCurrent(),
				'hints' => $hintList
			];
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
}