<?
namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Main\Localization\Loc;

class CrmContacts extends \Bitrix\Main\UI\Selector\EntityBase
{
	const PREFIX_SHORT = 'C_';
	const PREFIX_FULL = 'CRMCONTACT';

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
			'entityType' => 'contacts',
			'entityId' => $data['ID'],
			'name' => htmlspecialcharsbx(
				\CCrmContact::prepareFormattedName([
						'HONORIFIC' => isset($data['HONORIFIC']) ? $data['HONORIFIC'] : '',
						'NAME' => isset($data['NAME']) ? $data['NAME'] : '',
						'SECOND_NAME' => isset($data['SECOND_NAME']) ? $data['SECOND_NAME'] : '',
						'LAST_NAME' => isset($data['LAST_NAME']) ? $data['LAST_NAME'] : ''
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
			$res = \CCrmFieldMulti::getList(
				array('ID' => 'asc'),
				array(
					'ENTITY_ID' => \CCrmOwnerType::ContactName,
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
			$result['url'] = \CCrmOwnerType::getEntityShowPath(\CCrmOwnerType::Contact, $data['ID']);
			$result['urlUseSlider'] = (\CCrmOwnerType::isSliderEnabled(\CCrmOwnerType::Contact) ? 'Y' : 'N');
		}

		return $result;
	}

	public function getData($params = array())
	{
		$entityType = Handler::ENTITY_TYPE_CRMCONTACTS;

		$result = array(
			'ITEMS' => array(),
			'ITEMS_LAST' => array(),
			'ITEMS_HIDDEN' => array(),
			'ADDITIONAL_INFO' => array(
				'GROUPS_LIST' => array(
					'crmcontacts' => array(
						'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_CRMCONTACTS'),
						'TYPE_LIST' => [ $entityType ],
						'DESC_LESS_MODE' => 'N',
						'SORT' => 10
					)
				),
				'SORT_SELECTED' => 100
			)
		);

		$entityOptions = (!empty($params['options']) ? $params['options'] : array());
		$prefix = self::getPrefix($entityOptions);

		$lastItems = (!empty($params['lastItems']) ? $params['lastItems'] : array());
		$selectedItems = (!empty($params['selectedItems']) ? $params['selectedItems'] : array());

		$lastContactsIdList = [];
		if(!empty($lastItems[$entityType]))
		{
			$result["ITEMS_LAST"] = array_map(function($code) use ($prefix) { return preg_replace('/^'.self::PREFIX_FULL.'(\d+)$/', $prefix.'$1', $code); }, array_values($lastItems[$entityType]));
			foreach ($lastItems[$entityType] as $value)
			{
				$lastContactsIdList[] = str_replace(self::PREFIX_FULL, '', $value);
			}
		}

		$selectedContactsIdList = [];

		if(!empty($selectedItems[$entityType]))
		{
			foreach ($selectedItems[$entityType] as $value)
			{
				$selectedContactsIdList[] = str_replace($prefix, '', $value);
			}
		}

		$contactsIdList = array_merge($selectedContactsIdList, $lastContactsIdList);
		$contactsIdList = array_slice($contactsIdList, 0, count($selectedContactsIdList) > 20 ? count($selectedContactsIdList) : 20);
		$contactsIdList = array_unique($contactsIdList);

		$contactsList = [];

		$filter = [
			'CHECK_PERMISSIONS' => 'Y'
		];
		$order = [];

		if (!empty($contactsIdList))
		{
			$filter['ID'] = $contactsIdList;
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

		$res = \CCrmContact::getListEx(
			$order,
			$filter,
			false,
			$navParams,
			['ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO', 'HAS_EMAIL', 'DATE_CREATE']
		);

		while ($contactFields = $res->fetch())
		{
			$contactsList[$prefix.$contactFields['ID']] = self::prepareEntity($contactFields, $entityOptions);
		}

		if (empty($lastContactsIdList))
		{
			$result["ITEMS_LAST"] = array_keys($contactsList);
		}

		$result['ITEMS'] = $contactsList;

		if (!empty($selectedItems[$entityType]))
		{
			$hiddenItemsList = array_diff($selectedItems[$entityType], array_keys($contactsList));
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

				$res = \CCrmContact::getListEx(
					[],
					$filter,
					false,
					false,
					['ID']
				);
				while($contactFields = $res->fetch())
				{
					$result['ITEMS_HIDDEN'][] = $prefix.$contactFields["ID"];
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
					'id' => 'contacts',
					'name' => Loc::getMessage('MAIN_UI_SELECTOR_TAB_CRMCONTACTS'),
					'sort' => 20
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
			$searchParts = preg_split ('/[\s]+/', $search, 2, PREG_SPLIT_NO_EMPTY);
			if(count($searchParts) < 2)
			{
				$filter = [
					'SEARCH_CONTENT' => $search,
					'%FULL_NAME' => $search
				];
			}
			else
			{
				$filter = [
					'SEARCH_CONTENT' => $search,
					'LOGIC' => 'AND'
				];
				for($i = 0; $i < 2; $i++)
				{
					$filter["__INNER_FILTER_NAME_{$i}"] = [
						'%FULL_NAME' => $searchParts[$i]
					];
				}
			}

			if (
				isset($entityOptions['onlyWithEmail'])
				&& $entityOptions['onlyWithEmail'] == 'Y'
			)
			{
				$filter['=HAS_EMAIL'] = 'Y';
			}

			$res = \CCrmContact::getListEx(
				[],
				$filter,
				false,
				['nTopCount' => 20],
				['ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO', 'HAS_EMAIL', 'DATE_CREATE']
			);

			while ($contactFields = $res->fetch())
			{
				$result["ITEMS"][$prefix.$contactFields['ID']] = self::prepareEntity($contactFields, $entityOptions);
			}

			if (
				!empty($entityOptions['searchById'])
				&& $entityOptions['searchById'] == 'Y'
				&& intval($search) == $search
				&& intval($search) > 0
			)
			{
				$res = \CCrmContact::getListEx(
					[],
					[
						'=ID' => intval($search)
					],
					false,
					['nTopCount' => 1],
					['ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO', 'HAS_EMAIL', 'DATE_CREATE']
				);

				while ($contactFields = $res->fetch())
				{
					$result["ITEMS"][$prefix.$contactFields['ID']] = self::prepareEntity($contactFields, $entityOptions);
				}
			}
		}

		return $result;
	}
}