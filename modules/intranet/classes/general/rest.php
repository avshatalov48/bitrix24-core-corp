<?
if(!CModule::IncludeModule('rest'))
	return;

class CIntranetRestService extends IRestService
{
	const CONTACT_CENTER_PLACEMENT = 'CONTACT_CENTER';
	public const PAGE_BACKGROUND_WORKER_PLACEMENT = 'PAGE_BACKGROUND_WORKER';
	public const LEFT_MENU = 'LEFT_MENU';

	protected static $arAllowedDepartmentFields = array(
		"ID", "NAME", "SORT", "PARENT", "UF_HEAD"
	);
	protected static $arSelectDepartmentFields = array(
		"ID", "NAME", "SORT", "IBLOCK_SECTION_ID", "UF_HEAD"
	);

	public static function OnRestServiceBuildDescription()
	{
		$result = array(
			'department' => array(
				'department.fields' => array('CIntranetRestService', 'departmentFields'),
				'department.get' => array('CIntranetRestService', 'departmentGet'),
				'department.add' => array('CIntranetRestService', 'departmentAdd'),
				'department.update' => array('CIntranetRestService', 'departmentUpdate'),
				'department.delete' => array('CIntranetRestService', 'departmentDelete'),
			),
			'contact_center' => array(
				\CRestUtil::PLACEMENTS => array(
					self::CONTACT_CENTER_PLACEMENT => array()
				),
			),
			\CRestUtil::GLOBAL_SCOPE => [
				\CRestUtil::PLACEMENTS => [
					self::PAGE_BACKGROUND_WORKER_PLACEMENT => [
						'max_count' => 1,
						'user_mode' => true,
						'options' => [
							'errorHandlerUrl' => 'string',
						],
						'registerCallback' => [
							'moduleId' => 'intranet',
							'callback' => [
								'CIntranetRestService',
								'onRegisterPlacementPageBackground',
							],
						],
					],
					self::LEFT_MENU => [],
				],
			],
		);

		$placementMap = \Bitrix\Intranet\Binding\Menu::getRestMap();
		foreach ($placementMap as $scope => $placementList)
		{
			if (!empty($result[$scope][\CRestUtil::PLACEMENTS]))
			{
				$result[$scope][\CRestUtil::PLACEMENTS] = array_merge($placementList, $result[$scope][\CRestUtil::PLACEMENTS]);
			}
			else
			{
				$result[$scope][\CRestUtil::PLACEMENTS] = $placementList;
			}
		}

		return $result;
	}

	/**
	 * Check params on register INTRANET_PAGE_BACKGROUND.
	 * @param array $placementBind
	 * @param array $placementInfo
	 * @return array
	 */
	public static function onRegisterPlacementPageBackground(array $placementBind, array $placementInfo): array
	{
		if (empty($placementBind['OPTIONS']['errorHandlerUrl']))
		{
			$placementBind = [
				'error' => 'EMPTY_ERROR_HANDLER_URL',
				'error_description' => 'Field errorHandlerUrl is empty.',
			];
		}

		return $placementBind;
	}

	public static function departmentFields($arParams)
	{
		IncludeModuleLangFile(__FILE__);

		$arFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('IBLOCK_'.self::getDeptIblock().'_SECTION', 0, LANGUAGE_ID);

		$res = array(
			"ID" => "ID",
			"NAME" => GetMessage('INTR_STRUCTURE_NAME'),
			'SORT' => GetMessage('INTR_STRUCTURE_SORT'),
			'PARENT' => GetMessage('INTR_STRUCTURE_DEPARTMENT'),
			'UF_HEAD' => $arFields['UF_HEAD']['EDIT_FORM_LABEL'],
		);

		return $res;
	}

	public static function departmentGet($arQuery, $nav = 0)
	{
		CModule::IncludeModule('iblock');

		if(isset($arQuery['FILTER']) && is_array($arQuery['FILTER']))
		{
			$arQuery = $arQuery['FILTER'];
		}

		$dbRes = CIBlockSection::GetList(
			self::prepareSort($arQuery),
			self::prepareDeptData($arQuery),
			false,
			self::$arSelectDepartmentFields,
			self::getNavData($nav)
		);

		$result = array();
		$arFiles = array();
		while($arDept = $dbRes->NavNext(false))
		{
			$result[] = self::getDeptData($arDept);
		}

		return self::setNavData($result, $dbRes);
	}

	public static function departmentAdd($params)
	{
		if(self::canEdit())
		{
			CModule::IncludeModule('iblock');

			$params = array_change_key_case($params, CASE_UPPER);

			$arFields = array(
				'IBLOCK_ID' => self::getDeptIblock(),
				'NAME' => $params['NAME'],
				'SORT' => $params['SORT'],
				'IBLOCK_SECTION_ID' => $params['PARENT'],
				'UF_HEAD' => $params['UF_HEAD']
			);

			$ob = new CIBlockSection();
			$section = $ob->Add($arFields);
			if($section > 0)
			{
				return $section;
			}
			else
			{
				throw new Exception($ob->LAST_ERROR);
			}
		}
		else
		{
			throw new Exception('Access denied!');
		}
	}

	public static function departmentUpdate($params)
	{
		if(self::canEdit())
		{
			CModule::IncludeModule('iblock');

			$params = array_change_key_case($params, CASE_UPPER);

			$arDept = self::getDepartment($params['ID']);
			if(is_array($arDept))
			{
				$arFields = array();

				if(isset($params['NAME']))
					$arFields['NAME'] = $params['NAME'];
				if(isset($params['SORT']))
					$arFields['SORT'] = $params['SORT'];
				if(isset($params['PARENT']))
					$arFields['IBLOCK_SECTION_ID'] = $params['PARENT'];
				if(isset($params['UF_HEAD']))
					$arFields['UF_HEAD'] = $params['UF_HEAD'];

				if(count($arFields) > 0)
				{
					$ob = new CIBlockSection();
					if(!$ob->Update($arDept['ID'], $arFields))
					{
						throw new Exception($ob->LAST_ERROR);
					}
				}

				return true;
			}
			else
			{
				throw new Exception('Department not found');
			}
		}
		else
		{
			throw new Exception('Access denied!');
		}
	}

	public static function departmentDelete($params)
	{
		if(self::canEdit())
		{
			CModule::IncludeModule('iblock');

			$params = array_change_key_case($params, CASE_UPPER);

			$arDept = self::getDepartment($params['ID']);
			if(is_array($arDept))
			{
				$ob = new CIBlockSection();
				if(!$ob->Delete($arDept['ID']))
				{
					throw new Exception($ob->LAST_ERROR);
				}

				return true;
			}
			else
			{
				throw new Exception('Department not found');
			}
		}
		else
		{
			throw new Exception('Access denied!');
		}
	}

	protected static function getDeptIblock()
	{
		return COption::GetOptionInt('intranet', 'iblock_structure', 0);
	}

	protected static function getDeptData($arDept)
	{
		$res = array();
		foreach(self::$arSelectDepartmentFields as $key)
		{
			if(isset($arDept[$key]))
			{
				switch($key)
				{
					case 'SORT':
						$res[$key] = intval($arDept[$key]);
					break;
					case 'IBLOCK_SECTION_ID':
						$res['PARENT'] = $arDept[$key];
					break;
					default:
						$res[$key] = $arDept[$key];
				}
			}
		}

		return $res;
	}

	protected static function prepareSort($query): array
	{
		$query = array_change_key_case($query, CASE_UPPER);
		$sort = ['LEFT_MARGIN' => 'ASC'];

		if (isset($query['SORT']) && is_string($query['SORT']))
		{
			$sortField = mb_strtoupper($query['SORT']);
			if (in_array($sortField, self::$arAllowedDepartmentFields))
			{
				$order = isset($query['ORDER']) && is_string($query['ORDER']) ? mb_strtoupper($query['ORDER']) : '';
				if ($order != 'DESC')
					$order = 'ASC';

				$sort = [$sortField => $order];
			}
		}
		return $sort;
	}

	protected static function prepareDeptData($arData)
	{
		$arDept = array(
			'IBLOCK_ID' => self::getDeptIblock(),
			'GLOBAL_ACTIVE' => 'Y'
		);

		foreach($arData as $key => $value)
		{
			if(in_array($key, self::$arAllowedDepartmentFields))
			{
				$dkey = $key == 'PARENT' ? 'SECTION_ID' : $key;
				$arDept[$dkey] = $value;
			}
		}

		if(isset($arDept['ID']))
		{
			if(is_array($arDept['ID']))
				$arDept['ID'] = array_map("intval", $arDept['ID']);
			else
				$arDept['ID'] = intval($arDept['ID']);
		}

		if(isset($arDept['SORT']))
		{
			$arDept['SORT'] = intval($arDept['SORT']);
		}

		if(isset($arDept['SECTION_ID']))
		{
			if(is_array($arDept['SECTION_ID']))
				$arDept['SECTION_ID'] = array_map("intval", $arDept['SECTION_ID']);
			else
				$arDept['SECTION_ID'] = intval($arDept['SECTION_ID']);
		}

		if(isset($arDept['UF_HEAD']))
		{
			if(is_array($arDept['UF_HEAD']))
				$arDept['UF_HEAD'] = array_map("intval", $arDept['UF_HEAD']);
			else
				$arDept['UF_HEAD'] = intval($arDept['UF_HEAD']);
		}

		return $arDept;
	}

	protected static function getDepartment($ID)
	{
		$ID = intval($ID);
		if($ID > 0)
		{
			$dbRes = CIBlockSection::GetList(array(), array(
				'ID' => $ID,
				'IBLOCK_ID' => self::getDeptIblock()
			), false, array('ID'));
			return $dbRes->Fetch();
		}

		return false;
	}

	protected static function canEdit()
	{
		CModule::IncludeModule('iblock');
		$perm = CIBlock::GetPermission(self::getDeptIblock());
		return $perm  >= 'U';
	}
}
