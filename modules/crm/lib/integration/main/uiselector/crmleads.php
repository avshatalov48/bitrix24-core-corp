<?
namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Main\Localization\Loc;

class CrmLeads extends \Bitrix\Main\UI\Selector\EntityBase
{
	const PREFIX_SHORT = 'L_';
	const PREFIX_FULL = 'CRMLEAD';

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
		$prefix = self::getPrefix($options);
		$result = [
			'id' => $prefix.$data['ID'],
			'entityType' => 'leads',
			'entityId' => $data['ID'],
			'name' => htmlspecialcharsbx($data['TITLE']),
			'desc' => htmlspecialcharsbx(
				\CCrmLead::prepareFormattedName(
					[
						'HONORIFIC' => isset($data['HONORIFIC']) ? $data['HONORIFIC'] : '',
						'NAME' => isset($data['NAME']) ? $data['NAME'] : '',
						'SECOND_NAME' => isset($data['SECOND_NAME']) ? $data['SECOND_NAME'] : '',
						'LAST_NAME' => isset($data['LAST_NAME']) ? $data['LAST_NAME'] : ''
					]
				)
			)
		];

		if (array_key_exists('DATE_CREATE', $data))
		{
			$result['date'] = MakeTimeStamp($data['DATE_CREATE']);
		}

		if (
			!empty($data['HAS_EMAIL'])
			&& $data['HAS_EMAIL'] == 'Y'
		)
		{
			$res = \CCrmFieldMulti::getList(
				array('ID' => 'asc'),
				array(
					'ENTITY_ID' => \CCrmOwnerType::LeadName,
					'TYPE_ID' => \CCrmFieldMulti::EMAIL,
					'ELEMENT_ID' => $data['ID'],
				)
			);
			while ($multiFields = $res->Fetch())
			{
				if (!empty($multiFields['VALUE']))
				{
					$result['email'] = htmlspecialcharsbx($multiFields['VALUE']);
					break;
				}
			}
		}

		if (
			isset($options['returnItemUrl'])
			&& $options['returnItemUrl'] == 'Y'
		)
		{
			$result['url'] = \CCrmOwnerType::getEntityShowPath(\CCrmOwnerType::Lead, $data['ID']);
			$result['urlUseSlider'] = (\CCrmOwnerType::isSliderEnabled(\CCrmOwnerType::Lead) ? 'Y' : 'N');
		}

		return $result;
	}

	public function getData($params = array())
	{
		$entityType = Handler::ENTITY_TYPE_CRMLEADS;

		$result = array(
			'ITEMS' => array(),
			'ITEMS_LAST' => array(),
			'ITEMS_HIDDEN' => array(),
			'ADDITIONAL_INFO' => array(
				'GROUPS_LIST' => array(
					'crmleads' => array(
						'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_CRMLEADS'),
						'TYPE_LIST' => [ $entityType ],
						'DESC_LESS_MODE' => 'N',
						'SORT' => 40
					)
				),
				'SORT_SELECTED' => 400
			)
		);

		$entityOptions = (!empty($params['options']) ? $params['options'] : array());
		$prefix = self::getPrefix($entityOptions);

		$lastItems = (!empty($params['lastItems']) ? $params['lastItems'] : array());
		$selectedItems = (!empty($params['selectedItems']) ? $params['selectedItems'] : array());

		$lastLeadsIdList = [];
		if(!empty($lastItems[$entityType]))
		{
			$result["ITEMS_LAST"] = array_map(function($code) use ($prefix) { return preg_replace('/^'.self::PREFIX_FULL.'(\d+)$/', $prefix.'$1', $code); }, array_values($lastItems[$entityType]));
			foreach ($lastItems[$entityType] as $value)
			{
				$lastLeadsIdList[] = str_replace(self::PREFIX_FULL, '', $value);
			}
		}

		$selectedLeadsIdList = [];

		if(!empty($selectedItems[$entityType]))
		{
			foreach ($selectedItems[$entityType] as $value)
			{
				$selectedLeadsIdList[] = str_replace($prefix, '', $value);
			}
		}

		$leadsIdList = array_merge($selectedLeadsIdList, $lastLeadsIdList);
		$leadsIdList = array_slice($leadsIdList, 0, count($selectedLeadsIdList) > 20 ? count($selectedLeadsIdList) : 20);
		$leadsIdList = array_unique($leadsIdList);

		$leadsList = [];

		$filter = [
			'CHECK_PERMISSIONS' => 'Y'
		];
		$order = [];

		if (!empty($leadsIdList))
		{
			$filter['ID'] = $leadsIdList;
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

		$res = \CCrmLead::getListEx(
			$order,
			$filter,
			false,
			$navParams,
			['ID', 'TITLE', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'STATUS_ID', 'DATE_CREATE']
		);

		while ($leadFields = $res->fetch())
		{
			$leadsList[$prefix.$leadFields['ID']] = self::prepareEntity($leadFields, $entityOptions);
		}

		if (empty($lastLeadsIdList))
		{
			$result["ITEMS_LAST"] = array_keys($leadsList);
		}

		$result['ITEMS'] = $leadsList;

		if (!empty($selectedItems[$entityType]))
		{
			$hiddenItemsList = array_diff($selectedItems[$entityType], array_keys($leadsList));
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

				$res = \CCrmLead::getListEx(
					[],
					$filter,
					false,
					false,
					['ID']
				);
				while($leadFields = $res->fetch())
				{
					$result['ITEMS_HIDDEN'][] = $prefix.$leadFields["ID"];
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
					'id' => 'leads',
					'name' => Loc::getMessage('MAIN_UI_SELECTOR_TAB_CRMLEADS'),
					'sort' => 40
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
			$filter = [
				'LOGIC' => 'OR',
				'%FULL_NAME' => $search,
				'%TITLE' => $search,
			];

			$filter = array(
				'SEARCH_CONTENT' => $search,
				'__INNER_FILTER_1' => $filter
			);

			if (
				isset($entityOptions['onlyWithEmail'])
				&& $entityOptions['onlyWithEmail'] == 'Y'
			)
			{
				$filter['=HAS_EMAIL'] = 'Y';
			}

			$res = \CCrmLead::getListEx(
				[],
				$filter,
				false,
				['nTopCount' => 20],
				['ID', 'TITLE', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'STATUS_ID', 'DATE_CREATE']
			);

			while ($leadFields = $res->fetch())
			{
				$result["ITEMS"][$prefix.$leadFields['ID']] = self::prepareEntity($leadFields, $entityOptions);
			}

			if (
				!empty($entityOptions['searchById'])
				&& $entityOptions['searchById'] == 'Y'
				&& intval($search) == $search
				&& intval($search) > 0
			)
			{
				$res = \CCrmLead::getListEx(
					[],
					[
						'=ID' => intval($search)
					],
					false,
					['nTopCount' => 1],
					['ID', 'TITLE', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'STATUS_ID', 'DATE_CREATE']
				);

				while ($leadFields = $res->fetch())
				{
					$result["ITEMS"][$prefix.$leadFields['ID']] = self::prepareEntity($leadFields, $entityOptions);
				}
			}
		}

		return $result;
	}
}