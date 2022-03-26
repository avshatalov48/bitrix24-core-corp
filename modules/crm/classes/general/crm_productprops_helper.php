<?php
use Bitrix\Main\Loader;

if (!Loader::includeModule('iblock'))
{
	return false;
}

class CCrmProductPropsHelper
{
	public const OPERATION_VIEW = 'view';
	public const OPERATION_EDIT = 'edit';
	public const OPERATION_FILTER = 'filter';
	public const OPERATION_IMPORT = 'import';
	public const OPERATION_EXPORT = 'export';
	public const OPERATION_REST = 'rest';

	protected static $whiteListByOperation = null;
	protected static $blackList = null;
	protected static $typeListSupportingUrlTemplate = null;

	public static function GetUserTypeWhiteListByOperation()
	{
		if (self::$whiteListByOperation === null)
		{
			self::$whiteListByOperation = array(
				self::OPERATION_VIEW => array(),
				self::OPERATION_EDIT => array(),
				self::OPERATION_FILTER => array(),
				self::OPERATION_IMPORT => array(
					'S:HTML',
					'S:Date',
					'S:DateTime',
					'S:employee',
					'S:map_yandex',
					'S:ECrm',
					'S:Money',
					'N:Sequence'
				),
				self::OPERATION_EXPORT => array(
					'S:HTML',
					'S:Date',
					'S:DateTime',
					'S:employee',
					'S:map_yandex',
					'S:ECrm',
					'S:Money',
					'N:Sequence'
				),
				self::OPERATION_REST => array(
					'S:HTML',
					'S:Date',
					'S:DateTime',
					'S:employee',
					'S:map_yandex',
					'S:ECrm',
					'S:Money',
					'E:EList',
					'N:Sequence'
				)
			);
		}

		return self::$whiteListByOperation;
	}

	public static function GetUserTypeBlackList()
	{
		if (self::$blackList === null)
		{
			self::$blackList = array(
				'S:DiskFile',
				'S:directory',
				'G:SectionAuto',
				'E:EAutocomplete',
				'E:SKU'
			);
		}

		return self::$blackList;
	}

	/**
	 * @return array|null
	 */
	public static function getTypeListSupportingUrlTemplate(): ?array
	{
		if (self::$typeListSupportingUrlTemplate === null)
		{
			self::$typeListSupportingUrlTemplate = array(
				'E:EAutocomplete',
				'E:EList'
			);
		}

		return self::$typeListSupportingUrlTemplate;
	}

	/**
	 * @param array $propertyInfo
	 * @return bool
	 */
	public static function isTypeSupportingUrlTemplate(array $propertyInfo): bool
	{
		if (empty($propertyInfo['PROPERTY_TYPE']) || empty($propertyInfo['USER_TYPE']))
		{
			return false;
		}

		$urlTemplateSupportTypeList = static::getTypeListSupportingUrlTemplate();
		$fullType = "{$propertyInfo['PROPERTY_TYPE']}:{$propertyInfo['USER_TYPE']}";

		return in_array($fullType, $urlTemplateSupportTypeList, true);
	}

	public static function GetPropsTypesDescriptions($userType = false, $arOperations = array())
	{
		return \Bitrix\Iblock\Helpers\Admin\Property::getBaseTypeList(true);
	}

	public static function GetPropsTypesByOperations($userType = false, $arOperations = array())
	{
		if (!is_array($arOperations))
			$arOperations = array((string)$arOperations);

		$methodByOperation = array(
			self::OPERATION_VIEW => 'GetPublicViewHTML',
			self::OPERATION_EDIT => 'GetPublicEditHTML',
			self::OPERATION_FILTER => 'GetPublicFilterHTML',
			self::OPERATION_IMPORT => 'GetPublicEditHTML',
			self::OPERATION_EXPORT => 'GetPublicEditHTML',
			self::OPERATION_REST => 'GetPublicEditHTML',
		);

		$whiteListByOperation = self::GetUserTypeWhiteListByOperation();

		$blackList = self::GetUserTypeBlackList();

		$arUserTypeList = CIBlockProperty::GetUserType($userType);

		if (!empty($arOperations))
		{
			foreach ($arUserTypeList as $key => $item)
			{
				$skipNumber = count($arOperations);
				$skipCount = 0;
				foreach ($arOperations as $operation)
				{
					if (!isset($methodByOperation[$operation])
						|| !array_key_exists($methodByOperation[$operation], $item)
						|| (
							in_array($item['PROPERTY_TYPE'].':'.$key, $blackList, true)
							|| is_array($whiteListByOperation[$operation])
							&& count($whiteListByOperation[$operation]) > 0
							&& !in_array($item['PROPERTY_TYPE'].':'.$key, $whiteListByOperation[$operation], true)
						))
					{
						$skipCount++;
					}
				}
				if ($skipNumber <= $skipCount)
					unset($arUserTypeList[$key]);
			}
		}

		return $arUserTypeList;
	}
	public static function CanBeFiltered($userTypeList, $propertyInfo)
	{
		$result = false;

		if (!is_array($userTypeList) || !is_array($propertyInfo) ||
			(!empty($propertyInfo['USER_TYPE']) && !array_key_exists($propertyInfo['USER_TYPE'], $userTypeList)))
		{
			return $result;
		}

		if (!empty($propertyInfo['USER_TYPE']) &&
			is_array($userTypeList[$propertyInfo['USER_TYPE']]) &&
			array_key_exists('GetPublicFilterHTML', $userTypeList[$propertyInfo['USER_TYPE']]))
		{
			$result = true;
		}
		else if (empty($propertyInfo['USER_TYPE']) && $propertyInfo["PROPERTY_TYPE"] !== "F")
		{
			$result = true;
		}

		return $result;
	}

	public static function GetProps($catalogID, $arPropUserTypeList = array(), $arOperations = array())
	{
		if (!is_array($arOperations))
			$arOperations = array(strval($arOperations));

		$arProps = array();
		$catalogID = intval($catalogID);

		// validate operations list
		$validOperations = array(
			self::OPERATION_VIEW,
			self::OPERATION_EDIT,
			self::OPERATION_FILTER,
			self::OPERATION_IMPORT,
			self::OPERATION_EXPORT,
			self::OPERATION_REST
		);
		$validatedOperations = array();
		foreach ($arOperations as $operationName)
		{
			if (in_array(strval($operationName), $validOperations, true))
				$validatedOperations[] = $operationName;
		}
		$arOperations = $validatedOperations;
		unset($validatedOperations, $operationName);

		if ($catalogID > 0)
		{
			$propsFilter = array(
				'IBLOCK_ID' => $catalogID,
				'ACTIVE' => 'Y',
				'CHECK_PERMISSIONS' => 'N',
				'!PROPERTY_TYPE' => 'G'
			);

			$isImportOrExport = false;
			foreach ($arOperations as $operationName)
			{
				if ($operationName === self::OPERATION_IMPORT || $operationName === self::OPERATION_EXPORT)
				{
					$isImportOrExport = true;
				}
				else
				{
					$isImportOrExport = false;
					break;
				}
			}

			$dbRes = CIBlockProperty::GetList(
				array('SORT' => 'ASC', 'ID' => 'ASC'),
				$propsFilter
			);
			while ($arProp = $dbRes->Fetch())
			{
				if (
					(isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE'])
						&& !array_key_exists($arProp['USER_TYPE'], $arPropUserTypeList))
					|| (
						$isImportOrExport
						&& (
							($arProp['PROPERTY_TYPE'] === 'E'
								&& (!isset($arProp['USER_TYPE']) || empty($arProp['USER_TYPE'])))
							|| ($arProp['PROPERTY_TYPE'] === 'E'
								&& isset($arProp['USER_TYPE']) && $arProp['USER_TYPE'] === 'EList')
						)
					)
				)
				{
					continue;
				}

				$propID = 'PROPERTY_' . $arProp['ID'];
				$arProps[$propID] = $arProp;
			}
		}

		return $arProps;
	}

	public static function ListAddFilterFields($arPropUserTypeList, $arProps, $sFormName, &$arFilter, &$arFilterable,
												&$arCustomFilter, &$arDateFilter)
	{
		$i = count($arFilter);
		foreach ($arProps as $propID => $arProp)
		{
			if (!self::CanBeFiltered($arPropUserTypeList, $arProp) ||
				!isset($arProp['FILTRABLE']) || $arProp['FILTRABLE'] !== 'Y')
			{
				continue;
			}

			if (!empty($arProp['USER_TYPE'])
				&& is_array($arPropUserTypeList[$arProp['USER_TYPE']]))
			{
				if (array_key_exists('GetPublicFilterHTML', $arPropUserTypeList[$arProp['USER_TYPE']]))
				{
					$arFilter[$i] = array(
						'id' => $propID,
						'name' => $arProp['NAME'],
						'type' => 'custom',
						'enable_settings' => false,
						'value' => call_user_func_array(
							$arPropUserTypeList[$arProp['USER_TYPE']]['GetPublicFilterHTML'],
							array(
								$arProp,
								array(
									'VALUE'=>$propID,
									'FORM_NAME'=>'filter_'.$sFormName,
									'GRID_ID' => $sFormName,
								),
							)
						),
					);
					$arFilterable[$propID] = ($arProp['PROPERTY_TYPE'] === 'S') ? '?' : '';
					if (array_key_exists('AddFilterFields', $arPropUserTypeList[$arProp['USER_TYPE']]))
						$arCustomFilter[$propID] = array(
							'callback' => $arPropUserTypeList[$arProp['USER_TYPE']]['AddFilterFields'],
							'filter' => &$arFilter[$i],
						);
				}
			}
			else if (empty($arProp['USER_TYPE']))
			{
				if ($arProp["PROPERTY_TYPE"] === "F")
				{
				}
				else if ($arProp["PROPERTY_TYPE"] === "N")
				{
					$arFilter[$i] = array(
						"id" => $propID,
						"name" => $arProp["NAME"],
						"type" => "number",
					);
					$arFilterable[$propID] = "";
				}
				else if ($arProp["PROPERTY_TYPE"] === "G")
				{
					$items = array();
					$propSections = CIBlockSection::GetList(array("left_margin" => "asc"), array("IBLOCK_ID" => $arProp["LINK_IBLOCK_ID"]));
					while($arSection = $propSections->Fetch())
						$items[$arSection["ID"]] = str_repeat(". ", $arSection["DEPTH_LEVEL"]-1).$arSection["NAME"];
					unset($propSections, $arSection);

					$arFilter[$i] = array(
						"id" => $propID,
						"name" => $arProp["NAME"],
						"type" => "list",
						"items" => $items,
						"params" => array("size"=>5, "multiple"=>"multiple"),
						"valign" => "top",
					);
					$arFilterable[$propID] = "";
				}
				else if ($arProp["PROPERTY_TYPE"] === "E")
				{
					//Should be handled in template
					$arFilter[$i] = array(
						"id" => $propID,
						"name" => $arProp["NAME"],
						"type" => "propertyE",
						"value" => ""
					);
					$arFilterable[$propID] = "";
					$arCustomFilter[$propID] = array(
						'type' => 'propertyE',
						'filter' => &$arFilter[$i],
					);
				}
				else if ($arProp["PROPERTY_TYPE"] === "L")
				{
					$items = array();
					$propEnums = CIBlockProperty::GetPropertyEnum($arProp["ID"]);
					while($ar_enum = $propEnums->Fetch())
						$items[$ar_enum["ID"]] = $ar_enum["VALUE"];
					unset($propEnums);

					$arFilter[$i] = array(
						"id" => $propID,
						"name" => $arProp["NAME"],
						"type" => "list",
						"items" => $items,
						"params" => array("size"=>5, "multiple"=>"multiple"),
						"valign" => "top",
					);
					$arFilterable[$propID] = "";
				}
				else if ($arProp["PROPERTY_TYPE"] === 'S')
				{
					$arFilter[$i] = array(
						"id" => $propID,
						"name" => $arProp["NAME"],
					);
					$arFilterable[$propID] = "?";
				}
				else
				{
					$arFilter[$i] = array(
						"id" => $propID,
						"name" => $arProp["NAME"],
					);
					$arFilterable[$propID] = "";
				}
			}
			$i++;
		}
	}
	public static function ListAddHeades($arPropUserTypeList, $arProps, &$arHeaders)
	{
		foreach ($arProps as $propID => $arProp)
		{
			if (!empty($arProp['USER_TYPE']) && !array_key_exists($arProp['USER_TYPE'], $arPropUserTypeList))
				continue;

			if ((!empty($arProp['USER_TYPE'])
					&& is_array($arPropUserTypeList[$arProp['USER_TYPE']])
					&& array_key_exists('GetPublicViewHTML', $arPropUserTypeList[$arProp['USER_TYPE']]))
				|| empty($arProp['USER_TYPE']))
			{
				$arHeaders[] = array(
					'id' => $propID,
					'name' => htmlspecialcharsex($arProp['NAME']),
					'default' => false,
					'sort' => $arProp['MULTIPLE']=='Y'? '': $propID,
					'editable' => false
				);
			}
		}
	}

	public static function InternalizeCrmEntityValue(&$value, array $propertyInfo)
	{
		$settings = isset($propertyInfo['USER_TYPE_SETTINGS']) ? $propertyInfo['USER_TYPE_SETTINGS'] : null;
		if(!is_array($settings))
		{
			return;
		}

		$isContactEnabled = isset($settings['CONTACT']) && mb_strtoupper($settings['CONTACT']) === 'Y';
		$isCompanyEnabled = isset($settings['COMPANY']) && mb_strtoupper($settings['COMPANY']) === 'Y';
		$isLeadEnabled = isset($settings['LEAD']) && mb_strtoupper($settings['LEAD']) === 'Y';
		$isDealEnabled = isset($settings['DEAL']) && mb_strtoupper($settings['DEAL']) === 'Y';

		if(is_array($value))
		{
			foreach($value as $k => $v)
			{
				$entityID = 0;
				if($isLeadEnabled && self::TryInternalizeCrmEntityID(CCrmOwnerType::Lead, $v, $entityID))
				{
					$value[$k] = ($isContactEnabled || $isCompanyEnabled || $isDealEnabled)
						? "L_{$entityID}" : "{$entityID}";
				}
				elseif($isContactEnabled && self::TryInternalizeCrmEntityID(CCrmOwnerType::Contact, $v, $entityID))
				{
					$value[$k] = ($isCompanyEnabled || $isLeadEnabled || $isDealEnabled)
						? "C_{$entityID}" : "{$entityID}";
				}
				elseif($isCompanyEnabled && self::TryInternalizeCrmEntityID(CCrmOwnerType::Company, $v, $entityID))
				{
					$value[$k] = ($isContactEnabled || $isLeadEnabled || $isDealEnabled)
						? "CO_{$entityID}" : "{$entityID}";
				}
				elseif($isDealEnabled && self::TryInternalizeCrmEntityID(CCrmOwnerType::Deal, $v, $entityID))
				{
					$value[$k] = ($isContactEnabled || $isCompanyEnabled || $isLeadEnabled)
						? "D_{$entityID}" : "{$entityID}";
				}
			}
		}
		elseif(is_string($value) && $value !== '')
		{
			$entityID = 0;
			if($isLeadEnabled && self::TryInternalizeCrmEntityID(CCrmOwnerType::Lead, $value, $entityID))
			{
				$value = ($isContactEnabled || $isCompanyEnabled || $isDealEnabled)
					? "L_{$entityID}" : "{$entityID}";
			}
			elseif($isContactEnabled && self::TryInternalizeCrmEntityID(CCrmOwnerType::Contact, $value, $entityID))
			{
				$value = ($isCompanyEnabled || $isLeadEnabled || $isDealEnabled)
					? "C_{$entityID}" : "{$entityID}";
			}
			elseif($isCompanyEnabled && self::TryInternalizeCrmEntityID(CCrmOwnerType::Company, $value, $entityID))
			{
				$value = ($isContactEnabled || $isLeadEnabled || $isDealEnabled)
					? "CO_{$entityID}" : "{$entityID}";
			}
			elseif($isDealEnabled && self::TryInternalizeCrmEntityID(CCrmOwnerType::Deal, $value, $entityID))
			{
				$value = ($isContactEnabled || $isCompanyEnabled || $isLeadEnabled)
					? "D_{$entityID}" : "{$entityID}";
			}
		}
	}

	private static function TryInternalizeCrmEntityID($type, $value, &$id)
	{
		if(preg_match('/^\[([A-Z]+)\]/i', $value, $m) > 0)
		{
			$valueType = CCrmOwnerType::Undefined;
			$prefix = mb_strtoupper($m[1]);
			if($prefix === 'L')
			{
				$valueType = CCrmOwnerType::Lead;
			}
			elseif($prefix === 'C')
			{
				$valueType = CCrmOwnerType::Contact;
			}
			elseif($prefix === 'CO')
			{
				$valueType = CCrmOwnerType::Company;
			}
			elseif($prefix === 'D')
			{
				$valueType = CCrmOwnerType::Deal;
			}
			elseif($prefix === 'O')
			{
				$valueType = CCrmOwnerType::Order;
			}

			if($valueType !== CCrmOwnerType::Undefined && $valueType !== $type)
			{
				return false;
			}

			$value = mb_substr($value, mb_strlen($m[0]));
		}

		// 1. Try to interpret data as entity ID
		// 2. Try to interpret data as entity name
		if($type === CCrmOwnerType::Lead)
		{
			if(is_numeric($value))
			{
				$arEntity = CCrmLead::GetByID($value);
				if($arEntity)
				{
					$id = intval($arEntity['ID']);
					return true;
				}
			}

			$rsEntities = CCrmLead::GetListEx(array(), array('=TITLE'=> $value), false, false, array('ID'));
			while($arEntity = $rsEntities->Fetch())
			{
				$id = intval($arEntity['ID']);
				return true;
			}
		}
		elseif($type === CCrmOwnerType::Contact)
		{
			if(is_numeric($value))
			{
				$arEntity = CCrmContact::GetByID($value);
				if($arEntity)
				{
					$id = intval($arEntity['ID']);
					return true;
				}
			}

			// Try to interpret value as FULL_NAME
			$rsEntities = CCrmContact::GetListEx(array(), array('=FULL_NAME'=> $value, '@CATEGORY_ID' => 0, ), false, false, array('ID'));
			while($arEntity = $rsEntities->Fetch())
			{
				$id = intval($arEntity['ID']);
				return true;
			}

			if(preg_match('/\s*([^\s]+)\s+([^\s]+)\s*/', $value, $match) > 0)
			{
				// Try to interpret value as '#NAME# #LAST_NAME#'
				$rsEntities = CCrmContact::GetListEx(array(), array('=NAME'=> $match[1], '=LAST_NAME'=> $match[2], '@CATEGORY_ID' => 0,),  false, false, array('ID'));
				while($arEntity = $rsEntities->Fetch())
				{
					$id = intval($arEntity['ID']);
					return true;
				}

				// Try to interpret value as '#LAST_NAME# #NAME#'
				$rsEntities = CCrmContact::GetListEx(array(), array('=LAST_NAME'=> $match[1], '=NAME'=> $match[2], '@CATEGORY_ID' => 0, ),  false, false, array('ID'));
				while($arEntity = $rsEntities->Fetch())
				{
					$id = intval($arEntity['ID']);
					return true;
				}
			}
			else
			{
				// Try to interpret value as '#LAST_NAME#'
				$rsEntities = CCrmContact::GetListEx(array(), array('=LAST_NAME'=> $value, '@CATEGORY_ID' => 0,),  false, false, array('ID'));
				while($arEntity = $rsEntities->Fetch())
				{
					$id = intval($arEntity['ID']);
					return true;
				}
			}
		}
		elseif($type === CCrmOwnerType::Company)
		{
			if(is_numeric($value))
			{
				$arEntity = CCrmCompany::GetByID($value);
				if($arEntity)
				{
					$id = intval($arEntity['ID']);
					return true;
				}
			}

			$rsEntities = CCrmCompany::GetList(array(), array('=TITLE'=> $value, '@CATEGORY_ID' => 0,), array('ID'));
			while($arEntity = $rsEntities->Fetch())
			{
				$id = intval($arEntity['ID']);
				return true;
			}
		}
		elseif($type === CCrmOwnerType::Deal)
		{
			if(is_numeric($value))
			{
				$arEntity = CCrmDeal::GetByID($value);
				if($arEntity)
				{
					$id = intval($arEntity['ID']);
					return true;
				}
			}

			$rsEntities = CCrmDeal::GetList(array(), array('=TITLE'=> $value), array('ID'));
			while($arEntity = $rsEntities->Fetch())
			{
				$id = intval($arEntity['ID']);
				return true;
			}
		}
		return false;
	}

	public static function AjustExportMode ($exportType, $propertyInfo)
	{
		$result = 'CSV_EXPORT';

		if (!is_string($exportType) || $exportType === '')
		{
			$exportType = 'csv';
		}
		else
		{
			$exportType = mb_strtolower($exportType);
		}

		switch ($exportType)
		{
			case 'csv':
				$result = 'CSV_EXPORT';
				break;
			case 'excel':
				$propertyType = '';
				if (isset($propertyInfo['PROPERTY_TYPE']))
				{
					$propertyType .= $propertyInfo['PROPERTY_TYPE'];
				}
				if ($propertyType != '' && isset($propertyInfo['USER_TYPE']) && $propertyInfo['USER_TYPE'] != '')
				{
					$propertyType .= ':'.$propertyInfo['USER_TYPE'];
				}
				switch ($propertyType)
				{
					case 'S:DateTime':
					case 'S:map_yandex':
						$result = 'CSV_EXPORT';
						break;
					case 'S:HTML':
						$result = null;
						break;
					default:
						$result = 'EXCEL_EXPORT';
				}
				break;
		}

		return $result;
	}
}