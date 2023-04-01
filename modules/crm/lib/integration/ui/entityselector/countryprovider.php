<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\PhoneNumber\Parser;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use CUtil;

class CountryProvider extends BaseProvider
{
	public const ENTITY_ID = 'country';
	
	public const GLOBAL_COUNTRY_ID = '99999';
	public const GLOBAL_COUNTRY_CODE = 'XX';

	private const AVATAR_PATH = '/bitrix/js/crm/entity-selector/src/images/';
	private const AVATAR_EXT = 'png';
	private const METADATA_PATH = '/bitrix/js/main/phonenumber/metadata.json';

	protected string $metadataFilename;
	protected bool $isSortByCountryNameEnabled = true;
	protected bool $isEmptyCountryEnabled = true;

	public static function getIconByCode(string $code): string
	{
		$code = strlen($code) === 2 ? $code : static::GLOBAL_COUNTRY_CODE;

		return static::AVATAR_PATH . mb_strtolower($code) . '.' . static::AVATAR_EXT;
	}

	public static function getDefaultCountry(): string
	{
		$defaultCountry = Parser::getDefaultCountry();

		return empty($defaultCountry) ? static::GLOBAL_COUNTRY_CODE : $defaultCountry;
	}

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->metadataFilename = Application::getDocumentRoot() . static::METADATA_PATH;

		// set some default options
		if (isset($options['isSortByCountryName']))
		{
			$this->isSortByCountryNameEnabled = (bool)$options['isSortByCountryName'];
		}

		if (isset($options['isEmptyCountryEnabled']))
		{
			$this->isEmptyCountryEnabled = (bool)$options['isEmptyCountryEnabled'];
		}
	}

	public function isAvailable(): bool
	{
		return EntityAuthorization::isAuthorized();
	}

	public function fillDialog(Dialog $dialog): void
	{
		$items = $this->makeItems();

		array_walk(
			$items,
			static function (Item $item, int $index) use ($dialog) {
				// Show all countries sorted by name without context
				// When context is set, show all countries sorted by name with last recent items in the top
				if (empty($dialog->getContext()))
				{
					$item->setSort($index);
				}
				$dialog->addRecentItem($item);
			}
		);
	}

	public function getItems(array $ids): array
	{
		return $this->makeItems();
	}

	public function getSelectedItems(array $ids): array
	{
		return $this->makeItems();
	}

	private function makeItems(): array
	{
		$metadata = $this->fetchMetadata();
		$countries = $this->fetchCountries();
		if (empty($metadata) || empty($countries))
		{
			return [];
		}

		$items = [];
		foreach ($countries as $country)
		{
			if (!isset($metadata[$country['CODE']]))
			{
				continue; // skip not matched records
			}

			$items[] = $this->makeItem(
				(int)$country['ID'],
				(int)$metadata[$country['CODE']],
				(string)$country['CODE'],
				(string)$country['NAME']
			);
		}

		if ($this->isEmptyCountryEnabled)
		{
			array_unshift($items,$this->makeItem(
				static::GLOBAL_COUNTRY_ID,
				0,
				static::GLOBAL_COUNTRY_CODE,
				(string)Loc::getMessage('CRM_ENTITY_SELECTOR_EMPTY_COUNTRY')
			));
		}

		return $items;
	}

	private function makeItem(int $id, int $phoneCode, string $code, string $name): Item
	{
		$itemOptions = [
			'id' => $code,
			'entityId' => static::ENTITY_ID,
			'title' => $phoneCode === 0 ? $name : sprintf('%s (+%d)', $name, $phoneCode),
			'avatar' => static::getIconByCode($code),
			'customData' => [
				'countryId' => $id,
				'phoneCode' => $phoneCode,
			]
		];

		return new Item($itemOptions);
	}

	private function fetchCountries(): array
	{
		$countries = GetCountries();
		if (empty($countries))
		{
			return [];
		}

		if ($this->isSortByCountryNameEnabled)
		{
			$defaultCountry = Parser::getDefaultCountry();
			usort(
				$countries,
				static function(array $left, array $right) use ($defaultCountry)
				{
					return ($left['CODE'] === $defaultCountry) ? -1 : $left['NAME'] <=> $right['NAME'];
				}
			);
		}

		return $countries;
	}

	private function fetchMetadata(): array
	{
		if (file_exists($this->metadataFilename) && is_file($this->metadataFilename))
		{
			$allData = CUtil::JsObjectToPhp(file_get_contents($this->metadataFilename));
			$metadata = $allData['metadata'] ?? [];

			return array_column($metadata, 'countryCode', 'id');
		}

		return [];
	}
}
