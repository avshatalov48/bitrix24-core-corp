<?php

namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;
use CCrmQuote;

class CrmQuotes extends CrmBase
{
	public const PREFIX_SHORT = 'Q_';
	public const PREFIX_FULL = 'CRMQUOTE';

	protected static function getOwnerType(): int
	{
		return CCrmOwnerType::Quote;
	}

	protected static function getHandlerType(): string
	{
		return Handler::ENTITY_TYPE_CRMQUOTES;
	}

	protected static function prepareEntity($data, $options = [])
	{
		$clientTitle = (isset($data['COMPANY_TITLE'])) ? $data['COMPANY_TITLE'] : '';
		$clientTitle .= (
			$clientTitle <> ''
			&& isset($data['CONTACT_FULL_NAME'])
			&& $data['CONTACT_FULL_NAME'] <> ''
				? ', '
				: ''
		) . $data['CONTACT_FULL_NAME'];

		$prefix = static::getPrefix($options);
		$result = [
			'id' => $prefix . $data['ID'],
			'entityType' => 'quotes',
			'entityId' => $data['ID'],
			'name' => $data['ID'] . ' - '.htmlspecialcharsbx((str_replace([';', ','], ' ', $data['TITLE']))),
			'desc' => htmlspecialcharsbx($clientTitle),
		];

		if (
			isset($options['returnItemUrl'])
			&& $options['returnItemUrl'] == 'Y'
		)
		{
			$result['url'] = CCrmOwnerType::getEntityShowPath(CCrmOwnerType::Quote, $data['ID']);
			$result['urlUseSlider'] = (CCrmOwnerType::isSliderEnabled(CCrmOwnerType::Quote) ? 'Y' : 'N');
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
					'crmquotes' => [
						'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_CRMQUOTES_MSGVER_1'),
						'TYPE_LIST' => [ $entityType ],
						'DESC_LESS_MODE' => 'N',
						'SORT' => 80,
					],
				],
				'SORT_SELECTED' => 400,
			],
		];

		$entityOptions = (!empty($params['options']) ? $params['options'] : []);
		$prefix = static::getPrefix($entityOptions);

		$lastItems = (!empty($params['lastItems']) ? $params['lastItems'] : []);
		$selectedItems = (!empty($params['selectedItems']) ? $params['selectedItems'] : []);

		$lastQuotesIdList = [];
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
				$lastQuotesIdList[] = str_replace(self::PREFIX_FULL, '', $value);
			}
		}

		$selectedQuotesIdList = [];

		if(!empty($selectedItems[$entityType]))
		{
			foreach ($selectedItems[$entityType] as $value)
			{
				$selectedQuotesIdList[] = str_replace($prefix, '', $value);
			}
		}

		$quotesIdList = array_merge($selectedQuotesIdList, $lastQuotesIdList);
		$quotesIdList = array_slice($quotesIdList, 0, max(count($selectedQuotesIdList), 20));
		$quotesIdList = array_unique($quotesIdList);

		$quotesList = [];

		$filter = ['CHECK_PERMISSIONS' => 'Y'];
		$order = [ 'ID' => 'DESC' ];

		if (!empty($quotesIdList))
		{
			$filter['@ID'] = $quotesIdList;
			$navParams = false;
		}
		else
		{
			$navParams = [ 'nTopCount' => 10 ];
		}

		$res = CCrmQuote::getList(
			$order,
			$filter,
			false,
			$navParams,
			$this->getSearchSelect()
		);

		while ($quoteFields = $res->fetch())
		{
			$quotesList[$prefix . $quoteFields['ID']] = static::prepareEntity($quoteFields, $entityOptions);
		}

		if (empty($lastQuotesIdList))
		{
			$result["ITEMS_LAST"] = array_keys($quotesList);
		}

		$result['ITEMS'] = $quotesList;

		return $result;
	}

	public function getTabList($params = []): array
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
					'id' => 'quotes',
					'name' => Loc::getMessage('MAIN_UI_SELECTOR_TAB_CRMQUOTES_MSGVER_1'),
					'sort' => 80,
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

			$res = CCrmQuote::getList(
				$this->getSearchOrder(),
				$filter,
				false,
				[ 'nTopCount' => 20 ],
				$this->getSearchSelect()
			);

			$resultItems = [];
			while ($quoteFields = $res->fetch())
			{
				$resultItems[$prefix . $quoteFields['ID']] = static::prepareEntity($quoteFields, $entityOptions);
			}

			$resultItems = $this->appendItemsByIds($resultItems, $search, $entityOptions);

			$resultItems = $this->processResultItems($resultItems, $entityOptions);

			$result["ITEMS"] = $resultItems;
		}

		return $result;
	}

	protected function getSearchOrder(): array
	{
		return [ 'TITLE' => 'ASC' ];
	}

	protected function getSearchSelect(): array
	{
		return [
			'ID',
			'QUOTE_NUMBER',
			'TITLE',
			'STATUS_ID',
			'COMPANY_TITLE',
			'CONTACT_FULL_NAME',
		];
	}

	protected function getSearchFilter(string $search, array $options): array
	{
		$filter = [];

		if (is_numeric($search))
		{
			$filter['ID'] = (int) $search;
			$filter['%QUOTE_NUMBER'] = $search;
			$filter['%TITLE'] = $search;
			$filter['LOGIC'] = 'OR';
		}
		else if (preg_match('/( . *)\[(\d+?)\]/iu', $search, $matches))
		{
			$filter['ID'] = (int) $matches[2];
			$searchString = trim($matches[1]);
			if ($searchString !== '')
			{
				$filter['?TITLE'] = $searchString;
				$filter['LOGIC'] = 'OR';
			}
			unset($searchString);
		}
		else
		{
			$filter['?QUOTE_NUMBER'] = $search;
			$filter['?TITLE'] = $search;
			$filter['LOGIC'] = 'OR';
		}

		$filter = [
			'SEARCH_CONTENT' => $search,
			'__ENABLE_SEARCH_CONTENT_PHONE_DETECTION' => false,
			'__INNER_FILTER_1' => $filter
		];

		return $this->prepareOptionalFilter($filter, $options);
	}

	protected function getByIdsListMethodName(): string
	{
		return 'getList';
	}
}