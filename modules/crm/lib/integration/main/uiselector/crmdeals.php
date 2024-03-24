<?php

namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Main\Localization\Loc;
use CCrmContact;
use CCrmDeal;
use CCrmOwnerType;

class CrmDeals extends CrmBase
{
	public const PREFIX_SHORT = 'D_';
	public const PREFIX_FULL = 'CRMDEAL';

	protected const DATA_CLASS = CCrmDeal::class;
	protected const CACHE_DIR = 'b_crm_deal';

	protected static function getOwnerType(): int
	{
		return CCrmOwnerType::Deal;
	}

	protected static function getHandlerType(): string
	{
		return Handler::ENTITY_TYPE_CRMDEALS;
	}

	protected static function prepareEntity($data, $options = []): array
	{
		$prefix = static::getPrefix($options);
		$descList = [];
		if ($data['COMPANY_TITLE'] != '')
		{
			$descList[] = $data['COMPANY_TITLE'];
		}
		$descList[] = CCrmContact::PrepareFormattedName(
			[
				'HONORIFIC' => $data['CONTACT_HONORIFIC'] ?? '',
				'NAME' => $data['CONTACT_NAME'] ?? '',
				'SECOND_NAME' => $data['CONTACT_SECOND_NAME'] ?? '',
				'LAST_NAME' => $data['CONTACT_LAST_NAME'] ?? '',
			]
		);

		$result = [
			'id' => $prefix . $data['ID'],
			'entityType' => 'deals',
			'entityId' => $data['ID'],
			'name' => htmlspecialcharsbx($data['TITLE']),
			'desc' => htmlspecialcharsbx(implode(', ', $descList))
		];

		if (array_key_exists('DATE_CREATE', $data))
		{
			$result['date'] = MakeTimeStamp($data['DATE_CREATE']);
		}

		if (
			isset($options['returnItemUrl'])
			&& $options['returnItemUrl'] == 'Y'
		)
		{
			$result['url'] = CCrmOwnerType::getEntityShowPath(CCrmOwnerType::Deal, $data['ID']);
			$result['urlUseSlider'] = (CCrmOwnerType::isSliderEnabled(CCrmOwnerType::Deal) ? 'Y' : 'N');
		}

		return $result;
	}

	public function getData($params = []): array
	{
		$entityType = static::getHandlerType();

		$result = [
			'ITEMS' => [],
			'ITEMS_LAST' => [],
			'ITEMS_HIDDEN' => [],
			'ADDITIONAL_INFO' => [
				'GROUPS_LIST' => [
					'crmdeals' => [
						'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_CRMDEALS'),
						'TYPE_LIST' => [ $entityType ],
						'DESC_LESS_MODE' => 'N',
						'SORT' => 50,
					]
				],
				'SORT_SELECTED' => 500,
			],
		];

		$entityOptions = (!empty($params['options']) ? $params['options'] : []);
		$prefix = static::getPrefix($entityOptions);

		$lastItems = (!empty($params['lastItems']) ? $params['lastItems'] : []);
		$selectedItems = (!empty($params['selectedItems']) ? $params['selectedItems'] : []);

		$lastDealsIdList = [];
		if(!empty($lastItems[$entityType]))
		{
			$result["ITEMS_LAST"] = array_map(
				function($code) use ($prefix)
				{
					return preg_replace('/^'.self::PREFIX_FULL . '(\d+)$/', $prefix . '$1', $code);
				},
				array_values($lastItems[$entityType])
			);
			foreach ($lastItems[$entityType] as $value)
			{
				$lastDealsIdList[] = str_replace(self::PREFIX_FULL, '', $value);
			}
		}

		$selectedDealsIdList = [];

		if(!empty($selectedItems[$entityType]))
		{
			foreach ($selectedItems[$entityType] as $value)
			{
				$selectedDealsIdList[] = str_replace($prefix, '', $value);
			}
		}

		$dealsIdList = array_merge($selectedDealsIdList, $lastDealsIdList);
		$dealsIdList = array_slice($dealsIdList, 0, max(count($selectedDealsIdList), 20));
		$dealsIdList = array_unique($dealsIdList);

		$filter = ['CHECK_PERMISSIONS' => 'Y'];
		$order = [];

		if (!empty($dealsIdList))
		{
			$filter['ID'] = $dealsIdList;
			$navParams = false;
		}
		else
		{
			$order = ['ID' => 'DESC'];
			$navParams = [ 'nTopCount' => 10 ];
		}

		if (
			isset($entityOptions['onlyWithEmail'])
			&& $entityOptions['onlyWithEmail'] == 'Y'
		)
		{
			$filter['=HAS_EMAIL'] = 'Y';
		}

		$dealsList = $this->getEntitiesListEx(
			$order,
			$filter,
			false,
			$navParams,
			$this->getSearchSelect(),
			$entityOptions,
		);

		if (empty($lastDealsIdList))
		{
			$result["ITEMS_LAST"] = array_keys($dealsList);
		}

		$result['ITEMS'] = $dealsList;

		if (!empty($selectedItems[$entityType]))
		{
			$hiddenItemsList = array_diff($selectedItems[$entityType], array_keys($dealsList));
			$hiddenItemsList = array_map(
				function($code) use ($prefix)
				{
					return preg_replace('/^' . $prefix . '(\d+)$/', '$1', $code);
				},
				$hiddenItemsList
			);

			if (!empty($hiddenItemsList))
			{
				$filter = [
					'@ID' => $hiddenItemsList,
					'CHECK_PERMISSIONS' => 'N'
				];

				if (
					isset($entityOptions['onlyWithEmail'])
					&& $entityOptions['onlyWithEmail'] == 'Y'
				)
				{
					$filter['=HAS_EMAIL'] = 'Y';
				}

				$res = CCrmDeal::getListEx(
					[],
					$filter,
					false,
					false,
					['ID']
				);
				while($dealFields = $res->fetch())
				{
					$result['ITEMS_HIDDEN'][] = $prefix . $dealFields["ID"];
				}
			}
		}

		return $result;
	}

	public function getTabList($params = [])
	{
		$result = [];

		$options = (!empty($params['options']) ? $params['options'] : []);

		if (
			isset($options['addTab'])
			&& $options['addTab'] == 'Y'
		)
		{
			$result = [
				[
					'id' => 'deals',
					'name' => Loc::getMessage('MAIN_UI_SELECTOR_TAB_CRMDEALS'),
					'sort' => 50,
				],
			];
		}

		return $result;
	}

	public function search($params = []): array
	{
		$result = [
			'ITEMS' => [],
			'ADDITIONAL_INFO' => [],
		];

		$entityOptions = (!empty($params['options']) ? $params['options'] : []);
		$requestFields = (!empty($params['requestFields']) ? $params['requestFields'] : []);
		$search = $requestFields['searchString'];
		$prefix = static::getPrefix($entityOptions);

		if (
			$search <> ''
			&& (
				empty($entityOptions['enableSearch'])
				|| $entityOptions['enableSearch'] != 'N'
			)
		)
		{
			$filter = $this->getSearchFilter($search, $entityOptions);

			if ($filter === false)
			{
				return $result;
			}

			$res = CCrmDeal::getListEx(
				$this->getSearchOrder(),
				$filter,
				false,
				['nTopCount' => 20],
				$this->getSearchSelect()
			);

			$resultItems = [];

			while ($dealFields = $res->fetch())
			{
				$resultItems[$prefix . $dealFields['ID']] = static::prepareEntity($dealFields, $entityOptions);
			}

			$resultItems = $this->appendItemsByIds($resultItems, $search, $entityOptions);

			$resultItems = $this->processResultItems($resultItems, $entityOptions);

			$result["ITEMS"] = $resultItems;
		}

		return $result;
	}

	protected function getSearchSelect(): array
	{
		return [
			'ID',
			'TITLE',
			'COMPANY_TITLE',
			'CONTACT_NAME',
			'CONTACT_SECOND_NAME',
			'CONTACT_LAST_NAME',
			'CONTACT_HONORIFIC',
			'DATE_CREATE',
		];
	}

	protected function getSearchFilter(string $search, array $options): array
	{
		$filter = [
			'SEARCH_CONTENT' => $search,
			'?TITLE' => $search,
			'__ENABLE_SEARCH_CONTENT_PHONE_DETECTION' => false
		];

		return $this->prepareOptionalFilter($filter, $options);
	}
}