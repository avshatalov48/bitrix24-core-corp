<?
namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Emoji;

class CrmLeads extends CrmEntity
{
	const PREFIX_SHORT = 'L_';
	const PREFIX_FULL = 'CRMLEAD';

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

	private static function getOwnerType()
	{
		return \CCrmOwnerType::Lead;
	}

	private static function getOwnerTypeName()
	{
		return \CCrmOwnerType::LeadName;
	}

	private static function getHandlerType()
	{
		return Handler::ENTITY_TYPE_CRMLEADS;
	}

	private static function prepareEntity($data, $options = [])
	{
		$prefix = self::getPrefix($options);
		$result = [
			'id' => $prefix.$data['ID'],
			'entityType' => 'leads',
			'entityId' => $data['ID'],
			'name' => htmlspecialcharsbx(Emoji::decode($data['TITLE'])),
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
			$multiEmailsList = [];
			$found = false;

			$res = \CCrmFieldMulti::getList(
				array('ID' => 'asc'),
				array(
					'ENTITY_ID' => self::getOwnerTypeName(),
					'TYPE_ID' => \CCrmFieldMulti::EMAIL,
					'ELEMENT_ID' => $data['ID'],
				)
			);
			while ($multiFields = $res->Fetch())
			{
				if (!empty($multiFields['VALUE']))
				{
					$multiEmailsList[] = htmlspecialcharsbx($multiFields['VALUE']);
					if (!$found)
					{
						$result['email'] = htmlspecialcharsbx($multiFields['VALUE']);
						if (
							isset($options['onlyWithEmail'])
							&& $options['onlyWithEmail'] == 'Y'
						)
						{
							$result['desc'] = $result['email'];
						}
						$found = true;
					}
				}
			}
			$result['multiEmailsList'] = $multiEmailsList;
		}

		if (
			isset($options['returnItemUrl'])
			&& $options['returnItemUrl'] == 'Y'
		)
		{
			$result['url'] = \CCrmOwnerType::getEntityShowPath(self::getOwnerType(), $data['ID']);
			$result['urlUseSlider'] = (\CCrmOwnerType::isSliderEnabled(self::getOwnerType()) ? 'Y' : 'N');
		}

		return $result;
	}

	public function getData($params = [])
	{
		$entityType = Handler::ENTITY_TYPE_CRMLEADS;

		$result = [
			'ITEMS' => [],
			'ITEMS_LAST' => [],
			'ITEMS_HIDDEN' => [],
			'ADDITIONAL_INFO' => [
				'GROUPS_LIST' => [
					'crmleads' => [
						'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_CRMLEADS'),
						'TYPE_LIST' => [ $entityType ],
						'DESC_LESS_MODE' => 'N',
						'SORT' => 40
					]
				],
				'SORT_SELECTED' => 400
			]
		];

		$entityOptions = (!empty($params['options']) ? $params['options'] : array());
		$prefix = self::getPrefix($entityOptions);

		$lastItems = (!empty($params['lastItems']) ? $params['lastItems'] : array());
		$selectedItems = (!empty($params['selectedItems']) ? $params['selectedItems'] : array());

		$lastEntitiesIdList = [];
		if(!empty($lastItems[$entityType]))
		{
			$result["ITEMS_LAST"] = array_map(function($code) use ($prefix) { return preg_replace('/^'.self::PREFIX_FULL.'(\d+)$/', $prefix.'$1', $code); }, array_values($lastItems[$entityType]));
			foreach ($lastItems[$entityType] as $value)
			{
				$lastEntitiesIdList[] = str_replace(self::PREFIX_FULL, '', $value);
			}
		}
		if(!empty($lastItems[$entityType.'_MULTI']))
		{
			$result["ITEMS_LAST"] = array_merge($result["ITEMS_LAST"], array_map(function($code) use ($prefix) { $res = preg_replace_callback('/^'.self::PREFIX_FULL.'(\d+)(.+)$/', function($matches) use ($prefix) {return $prefix.$matches[1].mb_strtolower($matches[2]); }, $code); return $res;}, array_values($lastItems[$entityType.'_MULTI'])));
			foreach ($lastItems[$entityType.'_MULTI'] as $value)
			{
				$lastEntitiesIdList[] = preg_replace('/^'.self::PREFIX_FULL.'(\d+)(:([A-F0-9]{8}))$/', '$1', $value);
			}
		}

		$selectedEntitiesIdList = [];

		if(!empty($selectedItems[$entityType]))
		{
			foreach ($selectedItems[$entityType] as $value)
			{
				$selectedEntitiesIdList[] = str_replace($prefix, '', $value);
			}
		}
		if(!empty($selectedItems[$entityType.'_MULTI']))
		{
			foreach ($selectedItems[$entityType.'_MULTI'] as $value)
			{
				$selectedEntitiesIdList[] = preg_replace('/^'.self::PREFIX_FULL.'(\d+)(:([a-fA-F0-9]{8}))$/', '$1', $value);
			}
		}

		$entitiesIdList = array_merge($selectedEntitiesIdList, $lastEntitiesIdList);
		$entitiesIdList = array_slice($entitiesIdList, 0, count($selectedEntitiesIdList) > 20 ? count($selectedEntitiesIdList) : 20);
		$entitiesIdList = array_unique($entitiesIdList);

		$entitiesList = [];

		$filter = [
			'CHECK_PERMISSIONS' => 'Y'
		];
		$order = [];
		$select = ['ID', 'TITLE', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'STATUS_ID', 'DATE_CREATE', 'HAS_EMAIL'];

		if (!empty($entitiesIdList))
		{
			$filter['ID'] = $entitiesIdList;
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
			$select
		);

		while ($entityFields = $res->fetch())
		{
			$entitiesList[$prefix.$entityFields['ID']] = self::prepareEntity($entityFields, $entityOptions);
		}

		if (
			!empty($entitiesIdList)
			&& count($entitiesList) < 3
		)
		{
			unset($filter['ID']);
			$res = \CCrmLead::getListEx(
				[ 'ID' => 'DESC' ],
				$filter,
				false,
				[ 'nTopCount' => 10 ],
				$select
			);

			while ($entityFields = $res->fetch())
			{
				if (!isset($entitiesList[$prefix.$entityFields['ID']]))
				{
					$entitiesList[$prefix.$entityFields['ID']] = self::prepareEntity($entityFields, $entityOptions);
				}
			}
		}

		$entitiesList = self::processMultiFields($entitiesList, $entityOptions);

		if (empty($lastEntitiesIdList))
		{
			$result["ITEMS_LAST"] = array_keys($entitiesList);
		}

		$result['ITEMS'] = $entitiesList;

		if (!empty($selectedItems[$entityType]))
		{
			$hiddenItemsList = array_diff($selectedItems[$entityType], array_keys($entitiesList));
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
				while($entityFields = $res->fetch())
				{
					$result['ITEMS_HIDDEN'][] = $prefix.$entityFields["ID"];
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
		$resultItems = [];

		if (
			$search <> ''
			&& (
				empty($entityOptions['enableSearch'])
				|| $entityOptions['enableSearch'] != 'N'
			)
		)
		{
			$filter = false;

			if (check_email($search, true))
			{
				$entityIdList = [];
				$res = \CCrmFieldMulti::getList(
					[],
					[
						'ENTITY_ID' => \CCrmOwnerType::LeadName,
						'TYPE_ID' => \CCrmFieldMulti::EMAIL,
						'VALUE' => $search
					]
				);
				while($multiFields = $res->fetch())
				{
					$entityIdList[] = $multiFields['ELEMENT_ID'];
				}
				if (!empty($entityIdList))
				{
					$filter = [
						'@ID' => $entityIdList,
					];
				}
			}
			else
			{
				$filter = [
					'LOGIC' => 'OR',
					'%FULL_NAME' => $search,
					'%TITLE' => $search
				];

				$filter = array(
					'SEARCH_CONTENT' => $search,
					'__ENABLE_SEARCH_CONTENT_PHONE_DETECTION' => false,
					'__INNER_FILTER_1' => $filter
				);
			}

			if ($filter === false)
			{
				return $result;
			}

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
				['ID', 'TITLE', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'STATUS_ID', 'DATE_CREATE', 'HAS_EMAIL']
			);

			while ($entityFields = $res->fetch())
			{
				$resultItems[$prefix.$entityFields['ID']] = self::prepareEntity($entityFields, $entityOptions);
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
					['ID', 'TITLE', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'STATUS_ID', 'DATE_CREATE', 'HAS_EMAIL']
				);

				while ($entityFields = $res->fetch())
				{
					$resultItems[$prefix.$entityFields['ID']] = self::prepareEntity($entityFields, $entityOptions);
				}
			}

			$resultItems = self::processMultiFields($resultItems, $entityOptions);
			$result["ITEMS"] = $resultItems;
		}

		return $result;
	}
}