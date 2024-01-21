<?php

namespace Bitrix\Intranet\Settings;

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
		$data["isFormat24Hour"] = $this->is24HourFormat($currentSite);
		$data["format24HourTime"] = $this->get24HourTime();
		$data["format12HourTime"] = $this->get12HourTime();
		$data["culture"] = $this->getCultures($currentSite, $cultures);

		//mails section
		$data["trackOutMailsRead"] = $this->getStatusTrackOutMailsRead();
		$data["trackOutMailsClick"] = $this->getStatusTrackOutMailsClick();
		if ($this->isBitrix24)
		{
			$data['defaultEmailFrom'] = $this->getDefaultEmailFrom();
		}

		//CRM map section
		$data["yandexApiUrl"] = static::YANDEX_API_URL;
		$data["mapsProviderCRM"] = $this->getMapsProviderCRM();
		$mapSetting = $this->getMapsSettings();
		$data = array_merge($data, $mapSetting);

		//maps product properties section
		$data["googleApiUrl"] = static::GOOGLE_API_KEY;
		$data["cardsProviderProductProperties"] = $this->getCardsProviderProductProperties();
		$data["yandexKeyProductProperties"] = $this->getYandexKeyProductProperties();
		$data["googleKeyProductProperties"] = $this->getGoogleKeyProductProperties();

		//additional settings section
		$data["allowUserInstallApplication"] = $this->getUserInstallApplicationRight();

		if ($this->isBitrix24)
		{
			$data["allCanBuyTariff"] = $this->getAllCanBuyTariff();
		}

		$data["allowMeasureStressLevel"] = $this->getAllowMeasureStressLevel();
		$data["collectGeoData"] = $this->getCollectGeoData();
		$data["showSettingsAllUsers"] = $this->getShowSettingsAllUsers();

		return new static($data);
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
			'current' => $currentProvider
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
				'current' => $locationSourceCode
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

	private function getCultures(array $currentSite, array $cultures): array
	{
		$hintList = [];
		$values = [];
		$longDateList = [];
		$currentDate = '';

		foreach ($cultures as $culture)
		{
			$values[] = [
				'value' => $culture['ID'],
				'name' => $culture['NAME'],
				'selected' => $culture['ID'] === $currentSite["CULTURE_ID"],
			];

			$hintList[$culture['ID']] = $culture['LONG_DATE_FORMAT_USER'] . ' ' . $culture['SHORT_DATE_FORMAT_USER'];
			$longDateList[$culture['ID']] = $culture['LONG_DATE_FORMAT_USER'];

			if ($culture['ID'] === $currentSite["CULTURE_ID"])
			{
				$currentDate = $culture['LONG_DATE_FORMAT_USER'];
			}
		}

		return [
			'name' => 'culture',
			'values' => $values,
			'current' => $currentSite["CULTURE_ID"],
			'hints' => $hintList,
			'longDates' => $longDateList,
			'currentDate' => $currentDate,
			'offsetUTC' => $this->getOffsetUTC(),
		];
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

	private function is24HourFormat(array $currentSite): string
	{
		$currentTimeFormat = str_replace($currentSite["FORMAT_DATE"]." ", "", $currentSite["FORMAT_DATETIME"]);

		return $currentTimeFormat === 'HH:MI:SS' ? 'Y' : 'N';
	}
}