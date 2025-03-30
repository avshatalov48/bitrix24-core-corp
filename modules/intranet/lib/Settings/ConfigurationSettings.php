<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Bitrix24\Integration\Network\ProfileService;
use Bitrix\Bitrix24\Portal\Remove\Verification\VerificationFactory;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Settings\Controls\Section;
use Bitrix\Intranet\Settings\Controls\Selector;
use Bitrix\Intranet\Settings\Controls\Switcher;
use Bitrix\Intranet\Settings\Controls\Text;
use Bitrix\Intranet\Settings\Search\SearchEngine;
use Bitrix\Bitrix24\Portal;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Intranet\Integration\Main\Culture;
use Bitrix\Main\Config\Option;
use Bitrix\Location\Infrastructure\SourceCodePicker;
use Bitrix\Location\Repository\SourceRepository;
use Bitrix\Location\Entity\Source\OrmConverter;
use Bitrix\Location\Entity\Source\ConfigItem;
use Bitrix\Location;
use Bitrix\Main\Localization\Loc;
use Bitrix\Location\Entity\Source\Config;
use Bitrix\Main\Type\DateTime;
use Bitrix\Bitrix24\Feature;

class ConfigurationSettings extends AbstractSettings
{
	public const TYPE = 'configuration';

	private array $locationSourceRepository = [];
	private const YANDEX_API_URL = 'https://yandex.ru/dev/commercial/doc/ru/concepts/jsapi-geocoder#jsapi-geocoder';
	private const GOOGLE_API_KEY = 'https://developers.google.com/maps/documentation/javascript/get-api-key';
	private const TIME_FORMAT_VALUE_24 = 24;
	private const TIME_FORMAT_VALUE_12 = 12;
	private bool $isBitrix24;
	private ?DateTime $dateTime = null;

	public function __construct(array $data = [])
	{
		parent::__construct($data);
		$this->isBitrix24 = IsModuleInstalled("bitrix24");
	}

	public function validate(): ErrorCollection
	{
		$errors = new ErrorCollection();

		if ($this->isBitrix24 && (empty($this->data['defaultEmailFrom']) || !check_email($this->data['defaultEmailFrom'])))
		{
			$errors->setError(
				new Error(
					Loc::getMessage('SETTINGS_FORMAT_EMAIL_ERROR'),
					0,
					[
						'page' => $this->getType(),
						'field' => 'defaultEmailFrom',
					]
				)
			);
		}

		return $errors;
	}

	public function save(): Result
	{
		//date time section
		$this->setCulture();
		$this->setFormat24Hour();

		//mails section
		$this->setTrackOutMails();

		//CRM map section
		$this->setCardsProviderCRM();
		$this->setMapsSettingsCRM();

		//maps product properties section
		$this->setMapsProviderProductProperties();

		//additional settings section
		$this->setUserInstallApplicationRight();

		if ($this->isBitrix24)
		{
			$this->setAllCanBuyTariff();
			$this->setDefaultEmailFrom();
		}

		$this->setAllowMeasureStressLevel();
		$this->setCollectGeoData();
		$this->setShowSettingsAllUsers();

		return new Result();
	}

	private function setTrackOutMails(): void
	{
		if (isset($this->data["trackOutMailsRead"]))
		{
			Option::set(
				'main',
				'track_outgoing_emails_read',
				$this->data["trackOutMailsRead"]
			);
		}

		if (isset($this->data["trackOutMailsClick"]))
		{
			Option::set(
				'main',
				'track_outgoing_emails_click',
				$this->data["trackOutMailsClick"]
			);
		}
	}

	private function setCardsProviderCRM(): void
	{
		if (isset($this->data["cardsProviderCRM"]))
		{
			if (Loader::includeModule("location"))
			{
				SourceCodePicker::setSourceCode($this->data["cardsProviderCRM"]);
			}
		}
	}

	private function setFormat24Hour(): void
	{
		if (isset($this->data["isFormat24Hour"]))
		{
			if ($this->data["isFormat24Hour"] === 'Y')
			{
				$cultureFields['TIME_FORMAT_TYPE'] = static::TIME_FORMAT_VALUE_24;
			}
			else
			{
				$cultureFields['TIME_FORMAT_TYPE'] = static::TIME_FORMAT_VALUE_12;
			}
			Culture::updateCulture($cultureFields);
		}
	}

	private function setCulture(): void
	{
		if (isset($this->data["culture"]))
		{
			Culture::updateCurrentSiteCulture($this->data["culture"]);
		}
	}

	private function setMapsProviderProductProperties(): void
	{
		if (isset($this->data["yandexKeyProductProperties"]))
		{
			Option::set(
				'fileman',
				'yandex_map_api_key',
				$this->data["yandexKeyProductProperties"]
			);
		}

		if (isset($this->data["googleKeyProductProperties"]))
		{
			Option::set(
				'bitrix24',
				'google_map_api_key',
				$this->data["googleKeyProductProperties"]
			);
		}

		if (isset($this->data["cardsProviderProductProperties"]))
		{
			Option::set(
				'fileman',
				'cards_provider_product_properties',
				$this->data["cardsProviderProductProperties"]
			);

		}
	}

	private function setShowSettingsAllUsers(): void
	{
		if (isset($this->data["showSettingsAllUsers"]))
		{
			Option::set(
				'main',
				'show_settings_all_users',
				$this->data["showSettingsAllUsers"]
			);
		}
	}

	private function setCollectGeoData(): void
	{
		if (isset($this->data["collectGeoData"]))
		{
			Option::set(
				'main',
				'collect_geo_data',
				$this->data["collectGeoData"]
			);
		}
	}

	private function setAllowMeasureStressLevel(): void
	{
		if (isset($this->data["allowMeasureStressLevel"]))
		{
			Option::set(
				'intranet',
				'stresslevel_available',
				$this->data["allowMeasureStressLevel"]
			);
		}
	}

	private function setAllCanBuyTariff(): void
	{
		if (isset($this->data["allCanBuyTariff"]))
		{
			Option::set(
				'bitrix24',
				'buy_tariff_by_all',
				$this->data["allCanBuyTariff"]
			);
		}
	}

	private function setDefaultEmailFrom(): void
	{
		if (isset($this->data["defaultEmailFrom"]))
		{
			Option::set('main', 'email_from', $this->data["defaultEmailFrom"]);
		}
	}

	private function setUserInstallApplicationRight(): void
	{
		if (isset($this->data["allowUserInstallApplication"]))
		{
			if (Loader::includeModule("rest"))
			{
				\CRestUtil::setInstallAccessList($this->data["allowUserInstallApplication"] === 'Y' ? ['AU'] : []);
			}
		}
	}

	private function setMapsSettingsCRM(): void
	{
		foreach ($this->getLocationSourceRepository() as $source)
		{
			$sourceConfig = $source->getConfig() ?? new Config();

			foreach ($sourceConfig as $configItem)
			{
				if (!$configItem->isVisible())
				{
					continue;
				}
				if (!isset($this->data[$configItem->getCode()]))
				{
					continue;
				}

				$value = null;
				if ($configItem->getType() === ConfigItem::STRING_TYPE)
				{
					$value = $this->data[$configItem->getCode()];
				}
				elseif ($configItem->getType() === ConfigItem::BOOL_TYPE)
				{
					$value = $this->data[$configItem->getCode()] === 'Y';
				}

				$configItem->setValue($value);
			}
			$source->setConfig($sourceConfig);

			(new SourceRepository(new OrmConverter()))->save($source);
		}
	}

	public function get(): SettingsInterface
	{
		$data = [];
		//date time section
		$cultures = Culture::getCultures();
		$currentSite = Culture::getCurrentSite();
		$data["isFormat24Hour"] = new Switcher(
			'settings-configuration-field-is_format_24_hours',
			'isFormat24Hour',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_TIME_FORMAT24'),
			IsAmPmMode() ? 'N' : 'Y',
			[
				'hintTitle' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_TITLE_TIME_FORMAT24'),
				'on' => $this->get24HourTime(),
				'off' => $this->get12HourTime(),
			]
		);

		$data["format24HourTime"] = $this->get24HourTime();
		$data["format12HourTime"] = $this->get12HourTime();

		$currentDate = '';
		$longDateList = [];
		foreach ($cultures as $culture)
		{
			if ($culture['ID'] === $currentSite["CULTURE_ID"])
			{
				$currentDate = $culture['LONG_DATE_FORMAT_USER'];
			}
			$longDateList[$culture['ID']] = $culture['LONG_DATE_FORMAT_USER'];
		}
		$data['longDates'] = $longDateList;
		$data['currentDate'] = $currentDate;
		$data['offsetUTC'] = $this->getOffsetUTC();
		$data["culture"] = $this->getCultures($currentSite, $cultures);

		//region mails section
		$data["trackOutMailsRead"] = new Switcher(
			'settings-configuration-field-track_out_mails_read',
			'trackOutMailsRead',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_TRACK_OUT_MAILS'),
			$this->getStatusTrackOutMailsRead(),
			['on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_TRACK_OUT_MAILS_ON')],
		);

		$data["trackOutMailsClick"] = new Switcher(
			'settings-configuration-field-track_out_mails_click',
			'trackOutMailsClick',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_TRACK_OUT_MAILS_CLICKS'),
			$this->getStatusTrackOutMailsClick(),
			['on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_TRACK_OUT_MAILS_CLICK_ON_MSGVER_1')],
			helpDesk: 'redirect=detail&code=18213310'
		);
		if ($this->isBitrix24)
		{
			$data['defaultEmailFrom'] = new Text(
				'settings-configuration-field-default_email_from',
				'defaultEmailFrom',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_DEFAULT_EMAIL'),
				$this->getDefaultEmailFrom(),
				['hintTitle' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_DEFAULT_EMAIL')],
				Loc::getMessage('INTRANET_SETTINGS_FIELD_PLACEHOLDER_NOTIFICATION_EMAIL')
			);
		}
		//endregion
		//region CRM map section
		$data["yandexApiUrl"] = static::YANDEX_API_URL;
		$data["mapsProviderCRM"] = $this->getMapsProviderCRM();
		$mapSetting = $this->getMapsSettings();
		$data = array_merge($data, $mapSetting);
		//endregion

		//maps product properties section
		$data["googleApiUrl"] = static::GOOGLE_API_KEY;
		$data["cardsProviderProductProperties"] = $this->getCardsProviderProductProperties();
		$data["yandexKeyProductProperties"] = $this->getYandexKeyProductProperties();
		$data["googleKeyProductProperties"] = $this->getGoogleKeyProductProperties();

		//region additional settings section
		$data["allowUserInstallApplication"] = new Switcher(
			'settings-configuration-field-allowUserInstallApplication',
			'allowUserInstallApplication',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_ALL_USER_INSTALL_APPLICATION'),
			$this->getUserInstallApplicationRight(),
			['on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_ALLOW_ALL_USER_INSTALL_APPLICATION_CLICK_ON'),]
		);
		if ($this->isBitrix24)
		{
			$data["allCanBuyTariff"] = new Switcher(
				'settings-configuration-field-allCanBuyTariff',
				'allCanBuyTariff',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALL_CAN_BUY_TARIFF'),
				$this->getAllCanBuyTariff()['value'],
				['on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_ALL_CAN_BUY_TARIFF_CLICK_ON')],
				isEnable: $this->getAllCanBuyTariff()['isEnable'],
			);
		}

		$data["allowMeasureStressLevel"] = new Switcher(
			'settings-configuration-field-allowMeasureStressLevel',
			'allowMeasureStressLevel',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_MEASURE_STRESS_LEVEL'),
			$this->getAllowMeasureStressLevel(),
			['on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_ALLOW_MEASURE_STRESS_LEVEL_CLICK_ON_MSGVER_1')],
			helpDesk: 'redirect=detail&code=17697808',
		);

		//TODO: commented on issue task#488392
		/*$data["collectGeoData"] = new Switcher(
			'settings-configuration-field-collectGeoData',
			'collectGeoData',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_COLLECT_GEO_DATA'),
			$this->getCollectGeoData(),
			['on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_COLLECT_GEO_DATA_CLICK_ON_MSGVER_1')],
			helpDesk: 'redirect=detail&code=18213320',
		);*/

		$data["showSettingsAllUsers"] = $this->getShowSettingsAllUsers();
		//endregion


		//sections
		$data['sectionDateFormat'] = new Section(
			'settings-configuration-section-date_format',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_CONFIGURATION_DATETIME'),
			'ui-icon-set --clock-2',
		);

		$data['sectionLetters'] = new Section(
			'settings-configuration-section-letters',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_CONFIGURATION_MAILS'),
			'ui-icon-set --mail',
			false
		);

		if (!empty($data["mapsProviderCRM"]))
		{
			$data['sectionMapsInCrm'] = new Section(
				'settings-configuration-section-maps_in_crm',
				Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_CONFIGURATION_MAPS_LIST'),
				'ui-icon-set --crm-map',
				false
			);
		}

		$data['sectionMapsInProduct'] = new Section(
			'settings-configuration-section-maps_in_product',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_CONFIGURATION_MAPS_PRODUCT'),
			'ui-icon-set --location-2',
			false
		);

		$data['sectionOther'] = new Section(
			'settings-configuration-section-other',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_ADDITIONAL_SETTINGS'),
			'ui-icon-set --apps',
			false
		);

		if ($this->isBitrix24 && Option::get('bitrix24', 'is_delete_portal_feature_enabled') === 'Y')
		{
			$data['sectionDeletePortal'] = new Section(
				'settings-configuration-section-delete-portal',
				Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_DELETE_PORTAL'),
				'ui-icon-set --trash-bin',
				false
			);

			$isEmployeesLeft = \Bitrix\Bitrix24\License\UserActive::getInstance()->getCount() > 1;
			$isFreeLicense = \CBitrix24::isLicenseNeverPayed();
			$verificationOptions = null;

			if ($isFreeLicense)
			{
				$verificationOptions = $this->getVerificationOptions();
			}

			$data['deletePortalOptions'] = [
				'isEmployeesLeft' => $isEmployeesLeft,
				'portalUrl' => \Bitrix\Bitrix24\PortalSettings::getInstance()->getDomain()->getHostname(),
				'isFreeLicense' => $isFreeLicense,
				'mailForRequest' => Portal\Remove\RemoveValidator::getMailToRequest(),
				'verificationOptions' => $verificationOptions,
				'isAdmin' => \Bitrix\Bitrix24\CurrentUser::get()->isAdmin() && !\Bitrix\Bitrix24\CurrentUser::get()->isIntegrator()
			];
		}

		return new static($data);
	}

	private function getVerificationOptions(): ?array
	{
		try
		{
			$userId = CurrentUser::get()->getId();
			$profileContacts = ProfileService::getInstance()->fetchNetworkProfileContacts($userId);

			return (new VerificationFactory())->getVerificationOptionsByNetworkProfileContacts($profileContacts);
		}
		catch (\Exception $exception)
		{
			return null;
		}
	}

	private function getGoogleKeyProductProperties(): string
	{
		return Option::get(
			'bitrix24',
			'google_map_api_key',
			''
		);
	}

	private function getYandexKeyProductProperties(): string
	{
		return Option::get(
			'fileman',
			'yandex_map_api_key',
			''
		);
	}

	private function getShowSettingsAllUsers(): string
	{
		return Option::get(
			'main',
			'show_settings_all_users',
			'N'
		);
	}

	private function getCollectGeoData(): string
	{
		return Option::get(
			'main',
			'collect_geo_data',
			'N'
		);
	}

	private function getAllowMeasureStressLevel(): string
	{
		return Option::get(
			'intranet',
			'stresslevel_available',
			'Y'
		);
	}

	private function getUserInstallApplicationRight(): string
	{
		$result = 'N';
		if (Loader::includeModule("rest"))
		{
			$result = \CRestUtil::getInstallAccessList() === ['AU'] ? 'Y' : 'N';
		}

		return $result;
	}

	private function getMapsSettings(): array
	{
		$result = [];

		foreach ($this->getLocationSourceRepository() as $source)
		{
			$sourceCode = $source->getCode();
			if ($sourceCode === \Bitrix\Location\Entity\Source\Factory::OSM_SOURCE_CODE)
			{
				continue;
			}

			$config = $source->getConfig();
			foreach ($config as $configItem)
			{
				if (!$configItem->isVisible())
				{
					continue;
				}

				$code = $configItem->getCode();
				if (
					$configItem->getType() === ConfigItem::STRING_TYPE
					|| $configItem->getType() === ConfigItem::BOOL_TYPE
				)
				{
					$result[$code] = [
						'type' => $configItem->getType(),
						'sourceCode' => $sourceCode,
						'value' => $configItem->getValue()
					];
				}
			}
		}

		return $result;
	}

	private function getLocationSourceRepository(): array
	{
		if ($this->locationSourceRepository)
		{
			return $this->locationSourceRepository;
		}

		$result = [];

		if (Loader::includeModule("location"))
		{
			$result = (new SourceRepository(new OrmConverter()))->findAll();
		}

		return $result;
	}

	private function getCardsProviderProductProperties(): array
	{
		$currentProvider = Option::get(
			'fileman',
			'cards_provider_product_properties',
			'google'
		);

		$googleProvider = [
			'value' => 'google',
			'name' => Loc::getMessage('SETTINGS_MAPS_PROVIDER_GOOGLE'),
			'selected' => $currentProvider === 'google',
		];
		$values[] = $googleProvider;

		if (in_array(\CIntranetUtils::getPortalZone(), ['ru', 'by', 'kz']))
		{
			$yandexProvider = [
				'value' => 'yandex',
				'name' => Loc::getMessage('SETTINGS_MAPS_PROVIDER_YANDEX'),
				'selected' => $currentProvider === 'yandex',
			];
			$values[] = $yandexProvider;
		}


		$result = [
			'name' => 'cardsProviderProductProperties',
			'values' => $values,
			'current' => $currentProvider,
			'label' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_CHOOSE_REGION_CRM_MAPS'),
		];

		return $result;
	}

	private function getMapsProviderCRM(): ?array
	{
		if (Loader::includeModule("location") && Loader::includeModule("crm"))
		{
			$values = [];
			$locationSourceRepository = $this->getLocationSourceRepository();
			$locationSourceCode = SourceCodePicker::getSourceCode();

			foreach ($locationSourceRepository as $source)
			{
				$values[] = [
					'value' => $source->getCode(),
					'name' => $source->getName(),
					'selected' => $locationSourceCode === $source->getCode(),
				];
			}

			return [
				'name' => 'cardsProviderCRM',
				'values' => $values,
				'current' => $locationSourceCode,
				'label' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_CHOOSE_REGION_CRM_MAPS'),
			];
		}

		return null;
	}

	private function getAllCanBuyTariff(): array
	{
		return [
			'value' => Option::get('bitrix24', 'buy_tariff_by_all', 'Y'),
			'isEnable' => Feature::isFeatureEnabled('buy_tariff_by_all'),
		];
	}

	private function getStatusTrackOutMailsRead(): string
	{
		return Option::get(
			'main',
			'track_outgoing_emails_read',
			'Y'
		);
	}

	private function getStatusTrackOutMailsClick(): string
	{
		return Option::get(
			'main',
			'track_outgoing_emails_click',
			'Y'
		);
	}

	private function getDefaultEmailFrom(): string
	{
		return Option::get('main', 'email_from', '');
	}

	private function getCultures(array $currentSite, array $cultures): Selector
	{
		$hintList = ['hintTitle' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_TITLE_DATE_FORMAT')];
		$values = [];

		foreach ($cultures as $culture)
		{
			$values[] = [
				'value' => $culture['ID'],
				'name' => $culture['NAME'],
				'selected' => $culture['ID'] === $currentSite["CULTURE_ID"],
			];

			$hintList[$culture['ID']] = $culture['LONG_DATE_FORMAT_USER'] . ' ' . $culture['SHORT_DATE_FORMAT_USER'];
		}

		return new Selector(
			'settings-configuration-field-culture',
			'culture',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_DATETIME_REGION_FORMAT'),
			$values,
			$currentSite["CULTURE_ID"],
			hints: $hintList,
		);
	}

	private function getOffsetUTC(): string
	{
		if (Loader::includeModule("dav"))
		{
			$timeZoneList = \CTimeZone::GetZones();
			$currentTimeZone =  \CDavICalendarTimeZone::getTimeZoneId();
			$result = $timeZoneList[$currentTimeZone];
			$result = str_replace($currentTimeZone, '', $result);
		}
		else
		{
			$offset = (float)(($this->getCurrentDateTime()->format("Z") + \CTimeZone::GetOffset())/3600);
			$result = $offset.'UTC';
			if ($offset > 0)
			{
				$result .= '+';
			}
			$result .= $offset;
		}

		return $result;
	}

	private function getCurrentDateTime(): DateTime
	{
		if (is_null($this->dateTime))
		{
			$this->dateTime = new DateTime();
			$this->dateTime->toUserTime();
		}

		return $this->dateTime;
	}

	private function get24HourTime(): string
	{
		return $this->getCurrentDateTime()->format("H:i");
	}

	private function get12HourTime(): string
	{
		return $this->getCurrentDateTime()->format("g:i A");
	}

	public function find(string $query): array
	{
		$searchSections = [
			'settings-configuration-section-date_format' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_CONFIGURATION_DATETIME'),
			'settings-configuration-section-letters' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_CONFIGURATION_MAILS'),
			'settings-configuration-section-maps_in_product' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_CONFIGURATION_MAPS_PRODUCT'),
			'settings-configuration-section-other' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_ADDITIONAL_SETTINGS'),
		];

		if (!empty($this->getMapsProviderCRM()))
		{
			$searchSections['settings-configuration-section-maps_in_crm'] = Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_CONFIGURATION_MAPS_LIST');
		}

		$searchEngine = SearchEngine::initWithDefaultFormatter($searchSections + [
				'culture' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_DATETIME_REGION_FORMAT'),
				'isFormat24Hour' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_TIME_FORMAT24'),
				'trackOutMailsRead' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_TRACK_OUT_MAILS'),
				'trackOutMailsClick' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_TRACK_OUT_MAILS_CLICKS'),
				'defaultEmailFrom' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_DEFAULT_EMAIL'),
				'cardsProviderCRM' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_CHOOSE_REGION_CRM_MAPS'),
				'cardsProviderProductProperties' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_CHOOSE_REGION_CRM_MAPS'),
				'allowUserInstallApplication' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_ALL_USER_INSTALL_APPLICATION'),
				'allCanBuyTariff' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALL_CAN_BUY_TARIFF'),
				'allowMeasureStressLevel' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_MEASURE_STRESS_LEVEL'),
				//TODO: commented on issue task#488392
				//'collectGeoData' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_COLLECT_GEO_DATA'),
			]);

		return $searchEngine->find($query);
	}
}
