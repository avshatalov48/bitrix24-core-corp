<?
namespace Bitrix\Intranet\Integration\Main\UISelector;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class Departments extends \Bitrix\Main\UI\Selector\EntityBase
{
	private static function isExtranetUser()
	{
		return (
			Loader::includeModule('extranet')
			&& !\CExtranet::isIntranetUser()
		);
	}

	public function getData($params = array())
	{
		if (self::isExtranetUser())
		{
			return array();
		}

		$result = array(
			'ITEMS' => array(),
			'ITEMS_LAST' => array(),
			'ADDITIONAL_INFO' => array(
				'TYPE' => 'tree',
				'PREFIX' => 'DR',
				'RELATION_ENTITY_TYPE' => \Bitrix\Socialnetwork\Integration\Main\UISelector\Handler::ENTITY_TYPE_USERS,
				'GROUPS_LIST' => array(
					'departments' => array(
						'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_DEPARTMENTS'),
						'TYPE_LIST' => array(Handler::ENTITY_TYPE_DEPARTMENTS),
						'DESC_LESS_MODE' => 'Y',
						'SORT' => 30
					)
				),
				'SELECT_TEXT' => Loc::getMessage('MAIN_UI_SELECTOR_SELECT_TEXT_DEPARTMENTS'),
				'SELECT_FLAT_TEXT' => Loc::getMessage('MAIN_UI_SELECTOR_SELECT_FLAT_TEXT_DEPARTMENTS'),
				'ALLOW_SELECT' => 'N',
				'SORT_SELECTED' => 400
			)
		);

		if (!Loader::includeModule('socialnetwork'))
		{
			return $result;
		}

		$entityType = Handler::ENTITY_TYPE_DEPARTMENTS;
		$options = (!empty($params['options']) ? $params['options'] : array());
		$lastItems = (!empty($params['lastItems']) ? $params['lastItems'] : array());
		if (
			empty($lastItems[$entityType])
			&& !empty($lastItems['DEPARTMENT'])
		)
		{
			$lastItems[$entityType] = $lastItems['DEPARTMENT'];
			unset($lastItems['DEPARTMENT']);
		}

		if (
			!empty($options['siteDepartmentId'])
			&& $options['siteDepartmentId'] == 'EX'
		)
		{
			$structure = array(
				'department' => array(
					'EX' => array(
						'id' => 'EX',
						'entityId' => 'EX',
						'name' => GetMessage('MAIN_UI_SELECTOR_EXTRANET'),
						'parent' => 'DR0'
					)
				),
				'department_relation' => array(
					'EX' => array(
						'id' => 'EX',
						'items' => array(),
						'type' => 'category'
					)
				)
			);
		}
		else
		{
			$result['ADDITIONAL_INFO']['RELATION_ROOT'] = (!empty($options['siteDepartmentId']) ? intval($options['siteDepartmentId']) : 0);

			$structure = \CSocNetLogDestination::getStucture(array(
				'LAZY_LOAD' => true,
				'DEPARTMENT_ID' => (!empty($options['siteDepartmentId']) ? intval($options['siteDepartmentId']) : false)
			));
			if (
				!empty($options['enableFlat'])
				&& $options['enableFlat'] == 'Y'
				&& is_array($structure['department'])
			)
			{
				if (
					!empty($options['allowSelect'])
					&& $options['allowSelect'] == 'Y'
				)
				{
					$result['ADDITIONAL_INFO']['ALLOW_FLAT'] = 'Y';
				}

				foreach($structure['department'] as $code => $departmentData)
				{
					$structure['department'][$code]['idFlat'] = 'D'.$departmentData['entityId'];
				}
			}
		}

		if (
			!empty($options['allowSelect'])
			&& $options['allowSelect'] == 'Y'
		)
		{
			$result['ADDITIONAL_INFO']['ALLOW_SELECT'] = 'Y';
		}

		if (!empty($structure['department']))
		{
			foreach($structure['department'] as $itemCode => $item)
			{
				$result['ITEMS'][$itemCode] = $item;
				if (!empty($result['ITEMS'][$itemCode]['idFlat']))
				{
					$itemFlat = $item;
					$itemFlat['id'] = $item['idFlat'];
					$itemFlat['name'] = str_replace('#NAME#', $itemFlat['name'], Loc::getMessage('MAIN_UI_SELECTOR_DEPARTMENT_FLAT_PATTERN'));

					unset($itemFlat['idFlat']);
					unset($itemFlat['parent']);
					$result['ITEMS'][$result['ITEMS'][$itemCode]['idFlat']] = $itemFlat;
				}
			}
		}

		if(!empty($lastItems[$entityType]))
		{
			$result["ITEMS_LAST"] = array_values($lastItems[$entityType]);
		}

		return $result;
	}

	public function getTabList($params = array())
	{
		if (self::isExtranetUser())
		{
			return array();
		}

		$options = (!empty($params['options']) ? $params['options'] : array());

		return array(
			array(
				'id' => 'departments',
				'name' => Loc::getMessage('MAIN_UI_SELECTOR_TAB_DEPARTMENTS'),
				'sort' => 50
			)
		);
	}

	public function getItemName($itemCode = '')
	{
		$result = '';

		$entityId = (
			preg_match('/^DR(\d+)$/i', $itemCode, $matches)
			&& intval($matches[1]) > 0
				? intval($matches[1])
				: 0
		);

		if (
			$entityId  > 0
			&& !self::isExtranetUser()
		)
		{
			$res = \CIntranetUtils::getDepartmentsData(array($entityId));
			if (
				!empty($res)
				&& !empty($res[$entityId])
			)
			{
				$result =  $res[$entityId];
			}
		}

		return $result;
	}
}