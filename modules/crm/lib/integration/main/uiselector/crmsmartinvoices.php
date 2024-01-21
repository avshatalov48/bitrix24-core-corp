<?php

namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Crm\Filter\ItemDataProvider;
use Bitrix\Crm\Filter\ItemSettings;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\InvoiceSettings;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

class CrmSmartInvoices extends CrmDynamics
{
	public const PREFIX_SHORT = 'SI_';
	public const PREFIX_FULL = 'CRMSMART_INVOICE';

	protected static function getOwnerType(): int
	{
		return CCrmOwnerType::SmartInvoice;
	}

	protected static function getHandlerType(): string
	{
		return Handler::ENTITY_TYPE_CRMDYNAMICS;
	}

	protected static function prepareEntity(Item\Dynamic $item, ?array $options = []): array
	{
		$prefix = static::getPrefix($options);

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
			$result['url'] =
				Container::getInstance()->getRouter()->getItemDetailUrl(static::getOwnerType(), $item->getId())
			;
			$result['urlUseSlider'] = 'Y';
		}

		return $result;
	}

	public function getData($params = []): array
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
		$prefix = static::getPrefix($entityOptions);

		$lastItems = $params['lastItems'] ?? [];

		$lastEntitiesIdList = [];
		if(!empty($lastItems[$entityType . '_MULTI']))
		{
			if(!empty($lastItems[$entityType]))
			{
				$result['ITEMS_LAST'] = array_map(
					static function($code) use ($prefix) {
						return preg_replace('/^'.self::PREFIX_FULL . '(\d+)$/', $prefix . '$1', $code);
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

		$list = Container::getInstance()->getFactory(static::getOwnerType())->getItemsFilteredByPermissions([
			'order' => ['ID' => 'DESC'],
			'limit' => 10,
			'select' => [
				Item::FIELD_NAME_ID,
				Item::FIELD_NAME_TITLE,
				Item::FIELD_NAME_BEGIN_DATE,
				Item::FIELD_NAME_CREATED_TIME,
			],
		]);

		foreach ($list as $item)
		{
			$entitiesList[$prefix . $item['ID']] = static::prepareEntity($item, $entityOptions);
		}

		if (empty($lastEntitiesIdList))
		{
			$result['ITEMS_LAST'] = array_keys($entitiesList);
		}

		$result['ITEMS'] = $entitiesList;

		return $result;
	}

	public function getTabList($params = []): array
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
					'name' => CCrmOwnerType::GetDescription(static::getOwnerType()),
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
		$prefix = static::getPrefix($entityOptions);

		if (
			$search <> ''
			&& (
				empty($entityOptions['enableSearch'])
				|| $entityOptions['enableSearch'] !== 'N'
			)
		)
		{
			$filter = $this->getSearchFilter($search, $entityOptions);

			if ($filter === false)
			{
				return $result;
			}

			$list = Container::getInstance()->getFactory(static::getOwnerType())->getItemsFilteredByPermissions([
				'order' => $this->getSearchOrder(),
				'select' => $this->getSearchSelect(),
				'limit' => 10,
				'filter' => $filter,
			]);

			$resultItems = [];
			foreach ($list as $item)
			{
				$resultItems[$prefix . $item->getId()] = static::prepareEntity($item, $entityOptions);
			}

			$resultItems = $this->appendItemsByIds($resultItems, $search, $entityOptions);

			$resultItems = $this->processResultItems($resultItems, $entityOptions);

			$result["ITEMS"] = $resultItems;
		}

		return $result;
	}

	protected function getSearchFilter(string $search, array $options)
	{
		$filter = [];

		$entityTypeId = static::getOwnerType();
		$settings = new ItemSettings(
			['ID' => 'crm-element-field-' . $entityTypeId],
			Container::getInstance()->getTypeByEntityTypeId($entityTypeId)
		);
		$factory = Container::getInstance()->getFactory($entityTypeId);
		$provider = new ItemDataProvider($settings, $factory);
		$provider->prepareListFilter($filter, ['FIND' => $search]);

		return
			empty($filter)
				? false
				: $this->prepareOptionalFilter($filter, $options)
			;
	}

	protected function getByIdsResultItems(array $ids, array $options): array
	{
		$result = [];

		$prefix = static::getPrefix($options);

		$list = Container::getInstance()->getFactory(static::getOwnerType())->getItemsFilteredByPermissions(
			[
				'order' => $this->getByIdsOrder(),
				'select' => $this->getByIdsSelect(),
				'filter' => $this->getByIdsFilter($ids, $options),
			]
		);

		foreach ($list as $item)
		{
			$result[$prefix . $item->getId()] = static::prepareEntity($item, $options);
		}

		return $result;
	}

	protected static function getPrefix($options = []): string
	{
		if (!is_array($options))
		{
			$options = [];
		}

		$options['typeId'] = CCrmOwnerType::SmartInvoice;

		return parent::getPrefix($options);
	}
}
