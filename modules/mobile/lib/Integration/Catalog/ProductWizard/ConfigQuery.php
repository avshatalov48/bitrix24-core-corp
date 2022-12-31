<?php

namespace Bitrix\Mobile\Integration\Catalog\ProductWizard;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\Access\Permission\PermissionDictionary;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Mobile\Integration\Catalog\PermissionsProvider;

Loader::requireModule('catalog');
Loader::requireModule('crm');

final class ConfigQuery
{
	private const MAX_DICTIONARY_ITEMS = 500;

	private const WIZARD_TYPE_STORE = 'store';

	private const WIZARD_TYPE_CRM = 'crm';

	private string $wizardType;

	/** @var AccessController */
	private $accessController;

	public function __construct(string $wizardType)
	{
		$this->wizardType = $wizardType;
		$this->accessController = AccessController::getCurrent();
	}

	public function execute(): array
	{
		$dictionaries = [];

		if ($this->wizardType === self::WIZARD_TYPE_STORE)
		{
			$dictionaries = [
				'stores' => $this->getStoresList(),
				'measures' => $this->getMeasuresList(),
			];
		}
		elseif ($this->wizardType === self::WIZARD_TYPE_CRM)
		{
			$dictionaries = [
				'measures' => $this->getMeasuresList(),
				'taxes' => $this->getTaxConfig(),
				'inventoryControl' => $this->getInventoryControlConfig(),
			];
		}

		$dictionaries['permissions'] = PermissionsProvider::getInstance()->getPermissions();

		if (!empty($dictionaries))
		{
			return ['dictionaries' => $dictionaries];
		}

		return [];
	}

	private function getStoresList(): array
	{
		$allowedStores = $this->accessController->getPermissionValue(ActionDictionary::ACTION_STORE_VIEW);
		if (!$allowedStores)
		{
			return [];
		}

		$result = [];

		$filter = [
			'ACTIVE' => 'Y',
		];
		if (!in_array(PermissionDictionary::VALUE_VARIATION_ALL, $allowedStores, true))
		{
			$filter['=ID'] = $allowedStores;
		}

		$stores = \CCatalogStore::GetList(
			[
				'SORT' => 'ASC',
			],
			$filter,
			false,
			['nTopCount' => self::MAX_DICTIONARY_ITEMS],
			['ID', 'TITLE', 'ADDRESS','IS_DEFAULT',]
		);
		while ($store = $stores->Fetch())
		{
			$result[] = [
				'id' => $store['ID'],
				'title' => $store['TITLE'] == '' ? $store['ADDRESS'] : $store['TITLE'],
				'type' => 'store',
				'isDefault' => $store['IS_DEFAULT'] === 'Y',
			];
		}

		return $result;
	}

	private function getMeasuresList(): array
	{
		$result = [];

		$measures = \CCatalogMeasure::getList(
			[
				'CODE' => 'ASC'
			],
			[],
			false,
			['nTopCount' => self::MAX_DICTIONARY_ITEMS],
			['CODE', 'SYMBOL_RUS', 'SYMBOL_INTL', 'IS_DEFAULT', ]
		);

		while ($measure = $measures->Fetch())
		{
			$result[] = [
				'value' => (int)$measure['CODE'],
				'isDefault' => $measure['IS_DEFAULT'] === 'Y',
				'name' => $measure['SYMBOL_RUS'] ?? $measure['SYMBOL_INTL'],
			];
		}

		return $result;
	}

	private function getTaxConfig(): array
	{
		$vatRates = array_map(function ($fields) {
			return [
				'value' => $fields['ID'],
				'name' => $fields['NAME'],
				'rate' => (float)$fields['VALUE'],
			];
		}, \CCrmTax::GetVatRateInfos());

		$accounting = Container::getInstance()->getAccounting();

		return [
			'isTaxMode' => $accounting->isTaxMode(),
			'vatRates' => $vatRates,
			'vatIncluded' => false,
		];
	}

	private function getInventoryControlConfig(): array
	{
		return [
			'isInventoryControlEnabled' => \Bitrix\Catalog\Config\State::isUsedInventoryManagement(),
			'isQuantityControlEnabled' => Option::get('catalog', 'default_quantity_trace', 'N') === 'Y',
		];
	}
}
