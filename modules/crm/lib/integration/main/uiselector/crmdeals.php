<?
namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Main\Localization\Loc;

class CrmDeals extends \Bitrix\Main\UI\Selector\EntityBase
{
	const PREFIX_SHORT = 'D_';
	const PREFIX_FULL = 'CRMDEAL';

	private static function getPrefix($options = [])
	{
		return (
			is_array($options)
			&& isset($options['prefixType'])
			&& mb_strtolower($options['prefixType']) == 'short'
				? self::PREFIX_SHORT
				: self::PREFIX_FULL
		);
	}

	private static function prepareEntity($data, $options = [])
	{
		$prefix = self::getPrefix($options);
		$descList = [];
		if ($data['COMPANY_TITLE'] != '')
		{
			$descList[] = $data['COMPANY_TITLE'];
		}
		$descList[] = \CCrmContact::PrepareFormattedName(
			array(
				'HONORIFIC' => isset($data['CONTACT_HONORIFIC']) ? $data['CONTACT_HONORIFIC'] : '',
				'NAME' => isset($data['CONTACT_NAME']) ? $data['CONTACT_NAME'] : '',
				'SECOND_NAME' => isset($data['CONTACT_SECOND_NAME']) ? $data['CONTACT_SECOND_NAME'] : '',
				'LAST_NAME' => isset($data['CONTACT_LAST_NAME']) ? $data['CONTACT_LAST_NAME'] : ''
			)
		);

		$result = [
			'id' => $prefix.$data['ID'],
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
			$result['url'] = \CCrmOwnerType::getEntityShowPath(\CCrmOwnerType::Deal, $data['ID']);
			$result['urlUseSlider'] = (\CCrmOwnerType::isSliderEnabled(\CCrmOwnerType::Deal) ? 'Y' : 'N');
		}

		return $result;
	}

	public function getData($params = array())
	{
		$entityType = Handler::ENTITY_TYPE_CRMDEALS;

		$result = array(
			'ITEMS' => array(),
			'ITEMS_LAST' => array(),
			'ITEMS_HIDDEN' => array(),
			'ADDITIONAL_INFO' => array(
				'GROUPS_LIST' => array(
					'crmdeals' => array(
						'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_CRMDEALS'),
						'TYPE_LIST' => [ $entityType ],
						'DESC_LESS_MODE' => 'N',
						'SORT' => 50
					)
				),
				'SORT_SELECTED' => 500
			)
		);

		$entityOptions = (!empty($params['options']) ? $params['options'] : array());
		$prefix = self::getPrefix($entityOptions);

		$lastItems = (!empty($params['lastItems']) ? $params['lastItems'] : array());
		$selectedItems = (!empty($params['selectedItems']) ? $params['selectedItems'] : array());

		$lastDealsIdList = [];
		if(!empty($lastItems[$entityType]))
		{
			$result["ITEMS_LAST"] = array_map(function($code) use ($prefix) { return preg_replace('/^'.self::PREFIX_FULL.'(\d+)$/', $prefix.'$1', $code); }, array_values($lastItems[$entityType]));
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
		$dealsIdList = array_slice($dealsIdList, 0, count($selectedDealsIdList) > 20 ? count($selectedDealsIdList) : 20);
		$dealsIdList = array_unique($dealsIdList);

		$dealsList = [];

		$filter = [
			'CHECK_PERMISSIONS' => 'Y'
		];
		$order = [];

		if (!empty($dealsIdList))
		{
			$filter['ID'] = $dealsIdList;
			$navParams = false;
		}
		else
		{
			$order = [
				'ID' => 'DESC'
			];
			$navParams = [ 'nTopCount' => 10 ];
		}

		if (
			isset($entityOptions['onlyWithEmail'])
			&& $entityOptions['onlyWithEmail'] == 'Y'
		)
		{
			$filter['=HAS_EMAIL'] = 'Y';
		}

		$res = \CCrmDeal::getListEx(
			$order,
			$filter,
			false,
			$navParams,
			['ID', 'TITLE', 'COMPANY_TITLE', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME', 'CONTACT_HONORIFIC', 'DATE_CREATE']
		);

		while ($dealFields = $res->fetch())
		{
			$dealsList[$prefix.$dealFields['ID']] = self::prepareEntity($dealFields, $entityOptions);
		}

		if (empty($lastDealsIdList))
		{
			$result["ITEMS_LAST"] = array_keys($dealsIdList);
		}

		$result['ITEMS'] = $dealsList;

		if (!empty($selectedItems[$entityType]))
		{
			$hiddenItemsList = array_diff($selectedItems[$entityType], array_keys($dealsList));
			$hiddenItemsList = array_map(function($code) use ($prefix) { return preg_replace('/^'.$prefix.'(\d+)$/', '$1', $code); }, $hiddenItemsList);

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

				$res = \CCrmDeal::getListEx(
					[],
					$filter,
					false,
					false,
					['ID']
				);
				while($dealFields = $res->fetch())
				{
					$result['ITEMS_HIDDEN'][] = $prefix.$dealFields["ID"];
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
			$result = array(
				array(
					'id' => 'deals',
					'name' => Loc::getMessage('MAIN_UI_SELECTOR_TAB_CRMDEALS'),
					'sort' => 50
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
			$search <> ''
			&& (
				empty($entityOptions['enableSearch'])
				|| $entityOptions['enableSearch'] != 'N'
			)
		)
		{
			$res = \CCrmDeal::getListEx(
				[],
				[
					'SEARCH_CONTENT' => $search,
					'%TITLE' => $search,
					'__ENABLE_SEARCH_CONTENT_PHONE_DETECTION' => false
				],
				false,
				['nTopCount' => 20],
				['ID', 'TITLE', 'COMPANY_TITLE', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME', 'CONTACT_HONORIFIC', 'DATE_CREATE']
			);

			while ($dealFields = $res->fetch())
			{
				$result["ITEMS"][$prefix.$dealFields['ID']] = self::prepareEntity($dealFields, $entityOptions);
			}

			if (
				!empty($entityOptions['searchById'])
				&& $entityOptions['searchById'] == 'Y'
				&& intval($search) == $search
				&& intval($search) > 0
			)
			{
				$res = \CCrmDeal::getListEx(
					[],
					[
						'=ID' => intval($search)
					],
					false,
					['nTopCount' => 1],
					['ID', 'TITLE', 'COMPANY_TITLE', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME', 'CONTACT_HONORIFIC', 'DATE_CREATE']
				);

				while ($dealFields = $res->fetch())
				{
					$result["ITEMS"][$prefix.$dealFields['ID']] = self::prepareEntity($dealFields, $entityOptions);
				}
			}
		}

		return $result;
	}
}