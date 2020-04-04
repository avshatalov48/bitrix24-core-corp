<?
namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Main\Localization\Loc;

class CrmQuotes extends \Bitrix\Main\UI\Selector\EntityBase
{
	const PREFIX_SHORT = 'Q_';
	const PREFIX_FULL = 'CRMQUOTE';

	private static function getPrefix($options = [])
	{
		return (
			is_array($options)
			&& isset($options['prefixType'])
			&& strtolower($options['prefixType']) == 'short'
				? self::PREFIX_SHORT
				: self::PREFIX_FULL
		);
	}

	private static function prepareEntity($data, $options = [])
	{
		$clientTitle = (isset($data['COMPANY_TITLE'])) ? $data['COMPANY_TITLE'] : '';
		$clientTitle .= (
			strlen($clientTitle) > 0
			&& isset($data['CONTACT_FULL_NAME'])
			&& strlen($data['CONTACT_FULL_NAME']) > 0
				? ', '
				: ''
		).$data['CONTACT_FULL_NAME'];

		$prefix = self::getPrefix($options);
		$result = [
			'id' => $prefix.$data['ID'],
			'entityType' => 'quotes',
			'entityId' => $data['ID'],
			'name' => $data['ID'].' - '.htmlspecialcharsbx((str_replace(array(';', ','), ' ', $data['TITLE']))),
			'desc' => $clientTitle
		];

		if (
			isset($options['returnItemUrl'])
			&& $options['returnItemUrl'] == 'Y'
		)
		{
			$result['url'] = \CCrmOwnerType::getEntityShowPath(\CCrmOwnerType::Quote, $data['ID']);
			$result['urlUseSlider'] = (\CCrmOwnerType::isSliderEnabled(\CCrmOwnerType::Quote) ? 'Y' : 'N');
		}

		return $result;
	}

	public function getData($params = array())
	{
		$entityType = Handler::ENTITY_TYPE_CRMQUOTES;

		$result = array(
			'ITEMS' => array(),
			'ITEMS_LAST' => array(),
			'ITEMS_HIDDEN' => array(),
			'ADDITIONAL_INFO' => array(
				'GROUPS_LIST' => array(
					'crmquotes' => array(
						'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_CRMQUOTES'),
						'TYPE_LIST' => [ $entityType ],
						'DESC_LESS_MODE' => 'N',
						'SORT' => 80
					)
				),
				'SORT_SELECTED' => 400
			)
		);

		$entityOptions = (!empty($params['options']) ? $params['options'] : array());
		$prefix = self::getPrefix($entityOptions);

		$lastItems = (!empty($params['lastItems']) ? $params['lastItems'] : array());
		$selectedItems = (!empty($params['selectedItems']) ? $params['selectedItems'] : array());

		$lastQuotesIdList = [];
		if(!empty($lastItems[$entityType]))
		{
			$result["ITEMS_LAST"] = array_map(function($code) use ($prefix) { return preg_replace('/^'.self::PREFIX_FULL.'(\d+)$/', $prefix.'$1', $code); }, array_values($lastItems[$entityType]));
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
		$quotesIdList = array_slice($quotesIdList, 0, count($selectedQuotesIdList) > 20 ? count($selectedQuotesIdList) : 20);
		$quotesIdList = array_unique($quotesIdList);

		$quotesList = [];

		$filter = [
			'CHECK_PERMISSIONS' => 'Y'
		];
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

		if (!empty($quotesIdList))
		{
			$res = \CCrmQuote::getList(
				$order,
				$filter,
				false,
				$navParams,
				[ 'ID', 'TITLE', 'STAGE_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME' ]
			);

			while ($quoteFields = $res->fetch())
			{
				$quotesList[$prefix.$quoteFields['ID']] = self::prepareEntity($quoteFields, $entityOptions);
			}
		}

		if (empty($lastQuotesIdList))
		{
			$result["ITEMS_LAST"] = array_keys($quotesList);
		}

		$result['ITEMS'] = $quotesList;

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
			$result = array(
				array(
					'id' => 'quotes',
					'name' => Loc::getMessage('MAIN_UI_SELECTOR_TAB_CRMQUOTES'),
					'sort' => 80
				)
			);
		}

		return $result;
	}

	public function search($params = array())
	{
		$result = array(
			'ITEMS' => array(),
			'ADDITIONAL_INFO' => array()
		);

		$entityOptions = (!empty($params['options']) ? $params['options'] : array());
		$requestFields = (!empty($params['requestFields']) ? $params['requestFields'] : array());
		$search = $requestFields['searchString'];
		$prefix = self::getPrefix($entityOptions);

		if (
			strlen($search) > 0
			&& (
				empty($entityOptions['enableSearch'])
				|| $entityOptions['enableSearch'] != 'N'
			)
		)
		{
			$filter = array();
			if (is_numeric($search))
			{
				$filter['ID'] = (int) $search;
				$filter['%QUOTE_NUMBER'] = $search;
				$filter['%TITLE'] = $search;
				$filter['LOGIC'] = 'OR';
			}
			else if (preg_match('/(.*)\[(\d+?)\]/i'.BX_UTF_PCRE_MODIFIER, $search, $matches))
			{
				$filter['ID'] = (int) $matches[2];
				$searchString = trim($matches[1]);
				if (is_string($searchString) && $searchString !== '')
				{
					$filter['%TITLE'] = $searchString;
					$filter['LOGIC'] = 'OR';
				}
				unset($searchString);
			}
			else
			{
				$filter['%QUOTE_NUMBER'] = $search;
				$filter['%TITLE'] = $search;
				$filter['LOGIC'] = 'OR';
			}

			$filter = array(
				'SEARCH_CONTENT' => $search,
				'__INNER_FILTER_1' => $filter
			);

			$res = \CCrmQuote::getList(
				[ 'TITLE' => 'ASC' ],
				$filter,
				false,
				[ 'nTopCount' => 20 ],
				[ 'ID', 'QUOTE_NUMBER', 'TITLE', 'STATUS_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME' ]
			);

			while ($quoteFields = $res->fetch())
			{
				$result["ITEMS"][$prefix.$quoteFields['ID']] = self::prepareEntity($quoteFields, $entityOptions);
			}
		}

		return $result;
	}
}