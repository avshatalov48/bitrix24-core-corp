<?php

namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Crm\FieldMultiTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Emoji;
use CCrmContact;
use CCrmFieldMulti;
use CCrmOwnerType;

class CrmContacts extends CrmEntity
{
	public const PREFIX_SHORT = 'C_';
	public const PREFIX_FULL = 'CRMCONTACT';

	protected static function getOwnerType()
	{
		return CCrmOwnerType::Contact;
	}

	protected static function getHandlerType()
	{
		return Handler::ENTITY_TYPE_CRMCONTACTS;
	}

	protected static function prepareEntity($data, $options = [])
	{
		$prefix = static::getPrefix($options);
		$result = [
			'id' => $prefix . $data['ID'],
			'entityType' => 'contacts',
			'entityId' => $data['ID'],
			'name' => htmlspecialcharsbx(
				CCrmContact::prepareFormattedName([
						'HONORIFIC' => $data['HONORIFIC'] ?? '',
						'NAME' => isset($data['NAME']) ? Emoji::decode($data['NAME']) : '',
						'SECOND_NAME' => isset($data['SECOND_NAME']) ? Emoji::decode($data['SECOND_NAME']) : '',
						'LAST_NAME' => isset($data['LAST_NAME']) ? Emoji::decode($data['LAST_NAME']) : ''
				])
			),
			'desc' => htmlspecialcharsbx($data['COMPANY_TITLE'])
		];

		if (array_key_exists('DATE_CREATE', $data))
		{
			$result['date'] = MakeTimeStamp($data['DATE_CREATE']);
		}

		if (
			!empty($data['PHOTO'])
			&& intval($data['PHOTO']) > 0
		)
		{
			$imageFields = \CFile::resizeImageGet(
				$data['PHOTO'],
				['width' => 100, 'height' => 100],
				BX_RESIZE_IMAGE_EXACT
			);
			$result['avatar'] = $imageFields['src'];
		}

		if (
			!empty($data['HAS_EMAIL'])
			&& $data['HAS_EMAIL'] == 'Y'
		)
		{
			$multiEmailsList = [];
			$found = false;

			$res = CCrmFieldMulti::getList(
				['ID' => 'asc'],
				[
					'ENTITY_ID' => static::getOwnerTypeName(),
					'TYPE_ID' => CCrmFieldMulti::EMAIL,
					'ELEMENT_ID' => $data['ID'],
				]
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
			$result['url'] = CCrmOwnerType::getEntityShowPath(static::getOwnerType(), $data['ID']);
			$result['urlUseSlider'] = (CCrmOwnerType::isSliderEnabled(static::getOwnerType()) ? 'Y' : 'N');
		}

		return $result;
	}

	public function getData($params = [])
	{
		$entityType = static::getHandlerType();

		$result = [
			'ITEMS' => [],
			'ITEMS_LAST' => [],
			'ITEMS_HIDDEN' => [],
			'ADDITIONAL_INFO' => [
				'GROUPS_LIST' => [
					'crmcontacts' => [
						'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_CRMCONTACTS'),
						'TYPE_LIST' => [ $entityType ],
						'DESC_LESS_MODE' => 'N',
						'SORT' => 10
					]
				],
				'SORT_SELECTED' => 100
			]
		];

		$entityOptions = (!empty($params['options']) ? $params['options'] : []);
		$prefix = static::getPrefix($entityOptions);

		$lastItems = (!empty($params['lastItems']) ? $params['lastItems'] : []);
		$selectedItems = (!empty($params['selectedItems']) ? $params['selectedItems'] : []);

		$lastEntitiesIdList = [];
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
				$lastEntitiesIdList[] = str_replace(self::PREFIX_FULL, '', $value);
			}
		}
		if(!empty($lastItems[$entityType . '_MULTI']))
		{
			$result["ITEMS_LAST"] = array_merge(
				$result["ITEMS_LAST"],
				array_map(
					function($code) use ($prefix)
					{
						$res = preg_replace_callback(
							'/^'.self::PREFIX_FULL . '(\d+)( . +)$/',
							function($matches) use ($prefix)
							{
								return $prefix . $matches[1] . mb_strtolower($matches[2]);
							},
							$code
						);
						return $res;
					},
					array_values($lastItems[$entityType . '_MULTI'])
				)
			);
			foreach ($lastItems[$entityType . '_MULTI'] as $value)
			{
				$lastEntitiesIdList[] = preg_replace('/^'.self::PREFIX_FULL . '(\d+)(:([A-F0-9]{8}))$/', '$1', $value);
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
		if(!empty($selectedItems[$entityType . '_MULTI']))
		{
			foreach ($selectedItems[$entityType . '_MULTI'] as $value)
			{
				$selectedEntitiesIdList[] =
					preg_replace('/^'.self::PREFIX_FULL . '(\d+)(:([a-fA-F0-9]{8}))$/', '$1', $value)
				;
			}
			$selectedItems[$entityType] =
				array_map(function($item) { return self::PREFIX_FULL . $item; }, $selectedEntitiesIdList)
			;
			unset($selectedItems[$entityType . '_MULTI']);
		}

		$entitiesIdList = array_merge($selectedEntitiesIdList, $lastEntitiesIdList);
		$entitiesIdList = array_slice($entitiesIdList, 0, max(count($selectedEntitiesIdList), 20));
		$entitiesIdList = array_unique($entitiesIdList);

		$entitiesList = [];

		$filter = [
			'CHECK_PERMISSIONS' => 'Y',
			'@CATEGORY_ID' => 0,
		];
		$order = [];
		$select = $this->getSearchSelect();

		if (!empty($entitiesIdList))
		{
			$filter['ID'] = $entitiesIdList;
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

		$res = CCrmContact::getListEx(
			$order,
			$filter,
			false,
			$navParams,
			$select
		);

		while ($entityFields = $res->fetch())
		{
			$entitiesList[$prefix . $entityFields['ID']] = static::prepareEntity($entityFields, $entityOptions);
		}

		if (
			!empty($entitiesIdList)
			&& count($entitiesList) < 3
		)
		{
			unset($filter['ID']);
			$res = CCrmContact::getListEx(
				[ 'ID' => 'DESC' ],
				$filter,
				false,
				[ 'nTopCount' => 10 ],
				$select
			);

			while ($entityFields = $res->fetch())
			{
				if (!isset($entitiesList[$prefix . $entityFields['ID']]))
				{
					$entitiesList[$prefix . $entityFields['ID']] = static::prepareEntity($entityFields, $entityOptions);
				}
			}
		}

		$entitiesList = static::processMultiFields($entitiesList, $entityOptions);

		if (empty($lastEntitiesIdList))
		{
			$result["ITEMS_LAST"] = array_keys($entitiesList);
		}

		$result['ITEMS'] = $entitiesList;

		if (!empty($selectedItems[$entityType]))
		{
			$hiddenItemsList = array_diff($selectedItems[$entityType], array_keys($entitiesList));
			$hiddenItemsList = array_map(
				function($code) use ($prefix)
				{
					return preg_replace('/^' . $prefix . '(\d+)$/', '$1', $code);
				},
				$hiddenItemsList
			);

			if (!empty($hiddenItemsList))
			{
				$hiddenEntitiesList = [];

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

				$res = CCrmContact::getListEx(
					[],
					$filter,
					false,
					false,
					$select
				);
				while($entityFields = $res->fetch())
				{
					$hiddenEntitiesList[$prefix . $entityFields['ID']] =
						static::prepareEntity($entityFields, $entityOptions)
					;
				}

				if (!empty($hiddenEntitiesList))
				{
					$hiddenEntitiesList = static::processMultiFields($hiddenEntitiesList, $entityOptions);
					$result['ITEMS'] = array_merge($result['ITEMS'], $hiddenEntitiesList);
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
					'id' => 'contacts',
					'name' => Loc::getMessage('MAIN_UI_SELECTOR_TAB_CRMCONTACTS'),
					'sort' => 20,
				],
			];
		}

		return $result;
	}

	public function search($params = [])
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

			$res = CCrmContact::getListEx(
				$this->getSearchOrder(),
				$filter,
				false,
				['nTopCount' => 20],
				$this->getSearchSelect()
			);

			$resultItems = [];

			while ($entityFields = $res->fetch())
			{
				$resultItems[$prefix . $entityFields['ID']] = static::prepareEntity($entityFields, $entityOptions);
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
			'NAME',
			'SECOND_NAME',
			'LAST_NAME',
			'COMPANY_TITLE',
			'PHOTO',
			'HAS_EMAIL',
			'DATE_CREATE',
		];
	}

	protected function prepareOptionalFilter(array $filter, array $options): array
	{
		if (
			isset($options['onlyWithEmail'])
			&& $options['onlyWithEmail'] === 'Y'
		)
		{
			$filter['=HAS_EMAIL'] = 'Y';
		}

		return $filter;
	}

	protected function getSearchFilter(string $search, array $options)
	{
		$filter = false;

		$searchParts = preg_split ('/[\s]+/', $search, 2, PREG_SPLIT_NO_EMPTY);
		if(count($searchParts) < 2)
		{
			if (check_email($search, true))
			{
				$entityIdList = [];
				$query = FieldMultiTable::query()
					->where('ENTITY_ID', CCrmOwnerType::ContactName)
					->where('TYPE_ID', CCrmFieldMulti::EMAIL)
					->setSelect(['ELEMENT_ID'])
				;
				if (mb_substr($search, -1) === '%')
				{
					$query->whereLike('VALUE', $search);
				}
				else
				{
					$query->where('VALUE', $search);
				}
				$res = $query->exec();

				while($multiFields = $res->fetch())
				{
					$entityIdList[] = $multiFields['ELEMENT_ID'];
				}
				if (!empty($entityIdList))
				{
					$filter = ['@ID' => $entityIdList];
				}
			}
			else
			{
				$filter = [
					'SEARCH_CONTENT' => $search,
					'%FULL_NAME' => $search,
					'@CATEGORY_ID' => 0,
					'__ENABLE_SEARCH_CONTENT_PHONE_DETECTION' => false
				];
			}
		}
		else
		{
			$filter = [
				'SEARCH_CONTENT' => $search,
				'__ENABLE_SEARCH_CONTENT_PHONE_DETECTION' => false,
				'@CATEGORY_ID' => 0,
				'LOGIC' => 'AND'
			];

			for ($i = 0; $i < 2; $i++)
			{
				$filter["__INNER_FILTER_NAME_$i"] = ['%FULL_NAME' => $searchParts[$i]];
			}
		}

		return
			is_array($filter)
				? $this->prepareOptionalFilter($filter, $options)
				: false
		;
	}
}