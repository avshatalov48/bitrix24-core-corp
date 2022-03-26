<?php

namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Crm\Filter\ItemDataProvider;
use Bitrix\Crm\Filter\ItemSettings;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\InvoiceSettings;
use Bitrix\Main\Localization\Loc;

class CrmSmartInvoices extends CrmDynamics
{
	const PREFIX_SHORT = 'SI_';
	const PREFIX_FULL = 'CRMSMART_INVOICE';

	protected static function getPrefix($options = [])
	{
		return (
			is_array($options)
			&& isset($options['prefixType'])
			&& mb_strtolower($options['prefixType']) === 'short'
				? self::PREFIX_SHORT
				: self::PREFIX_FULL
		);
	}

	protected static function prepareEntity(Item\Dynamic $item, ?array $options = []): array
	{
		$prefix = self::getPrefix($options);

		$date = $item->getBegindate() ?? $item->getCreatedTime();

		$result = [
			'id' => $prefix . $item->getId(),
			'entityType' => 'smart_invoices',
			'entityId' => $item->getId(),
			'name' => htmlspecialcharsbx($item->getHeading()),
			'desc' => '',
		];

		if ($date)
		{
			$result['date'] = $date->getTimestamp();
		}

		if (
			isset($options['returnItemUrl'])
			&& $options['returnItemUrl'] === 'Y'
		)
		{
			$result['url'] = Container::getInstance()->getRouter()->getItemDetailUrl(\CCrmOwnerType::SmartInvoice, $item->getId());
			$result['urlUseSlider'] = 'Y';
		}

		return $result;
	}

	public function getData($params = [])
	{
		$entityType = Handler::ENTITY_TYPE_CRMSMART_INVOICES;

		$result = [
			'ITEMS' => [],
			'ITEMS_LAST' => [],
			'ITEMS_HIDDEN' => [],
			'ADDITIONAL_INFO' => [
				'GROUPS_LIST' => [
					'crmsmart_invoices' => [
						'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_CRMSMART_INVOICE'),
						'TYPE_LIST' => [ $entityType ],
						'DESC_LESS_MODE' => 'N',
						'SORT' => 40
					]
				],
				'SORT_SELECTED' => 400
			]
		];

		if (!InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
		{
			return $result;
		}

		$entityOptions = $params['options'] ?? [];
		$prefix = self::getPrefix($entityOptions);

		$lastItems = $params['lastItems'] ?? [];

		$lastEntitiesIdList = [];
		if(!empty($lastItems[$entityType.'_MULTI']))
		{
			$lastEntitiesIdList = [];
			if(!empty($lastItems[$entityType]))
			{
				$result['ITEMS_LAST'] = array_map(
					static function($code) use ($prefix) {
						return preg_replace('/^'.self::PREFIX_FULL.'(\d+)$/', $prefix.'$1', $code);
					},
					array_values($lastItems[$entityType])
				);
				foreach ($lastItems[$entityType] as $value)
				{
					$lastEntitiesIdList[] = str_replace(self::PREFIX_FULL, '', $value);
				}
			}
		}

		$entitiesList = [];

		$list = Container::getInstance()->getFactory(\CCrmOwnerType::SmartInvoice)->getItemsFilteredByPermissions([
			'order' => ['ID' => 'DESC'],
			'limit' => 10,
		]);

		foreach ($list as $item)
		{
			$entitiesList[$prefix . $item['ID']] = self::prepareEntity($item, $entityOptions);
		}

		if (empty($lastEntitiesIdList))
		{
			$result['ITEMS_LAST'] = array_keys($entitiesList);
		}

		$result['ITEMS'] = $entitiesList;

		return $result;
	}

	public function getTabList($params = [])
	{
		$result = [];

		$options = $params['options'] ?? [];

		if (
			isset($options['addTab'])
			&& $options['addTab'] === 'Y'
		)
		{
			$result = [
				[
					'id' => 'smart_invoices',
					'name' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::SmartInvoice),
					'sort' => 40
				]
			];
		}

		return $result;
	}

	public function search($params = []): array
	{
		$result = [
			'ITEMS' => [],
			'ADDITIONAL_INFO' => []
		];

		if (!InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
		{
			return $result;
		}

		$entityOptions = $params['options'] ?? [];
		$requestFields = $params['requestFields'] ?? [];
		$search = $requestFields['searchString'];
		$prefix = self::getPrefix($entityOptions);
		$entityTypeId = \CCrmOwnerType::SmartInvoice;

		if (
			$search <> ''
			&& (
				empty($entityOptions['enableSearch'])
				|| $entityOptions['enableSearch'] !== 'N'
			)
		)
		{
			$filter = [];

			$settings = new ItemSettings([
				'ID' => 'crm-element-field-'.$entityTypeId,
			], Container::getInstance()->getTypeByEntityTypeId($entityTypeId));
			$factory = Container::getInstance()->getFactory($entityTypeId);
			$provider = new ItemDataProvider($settings, $factory);
			$provider->prepareListFilter($filter, ['FIND' => $search]);

			$list = $factory->getItemsFilteredByPermissions([
				'select' => ['*'],
				'limit' => 10,
				'filter' => $filter,
			]);

			$resultItems = [];
			foreach ($list as $item)
			{
				$resultItems[$prefix . $item->getId()] = self::prepareEntity($item, $entityOptions);
			}

			$result['ITEMS'] = $resultItems;
		}

		return $result;
	}
}