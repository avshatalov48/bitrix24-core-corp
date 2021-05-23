<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('iblock'))
{
	ShowError(GetMessage('CRM_IBLOCK_MODULE_NOT_INSTALLED'));
	return;
}

/** @global CDatabase $DB*/
/** @global CMain $APPLICATION */
/** @global CUser $USER */
global $DB, $APPLICATION, $USER;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arParams['PATH_TO_PRODUCTPROPS_LIST'] = CrmCheckPath('PATH_TO_PRODUCTPROPS_LIST', $arParams['PATH_TO_PRODUCTPROPS_LIST'], '');
$arParams['PATH_TO_PRODUCTPROPS_EDIT'] = CrmCheckPath('PATH_TO_PRODUCTPROPS_EDIT', $arParams['PATH_TO_PRODUCTPROPS_EDIT'], '?prop_id=#prop_id#&edit');

$propID = isset($arParams['PROP_ID']) ? intval($arParams['PROP_ID']) : 0;

$iblockID = intval(CCrmCatalog::EnsureDefaultExists());
$iblockTypeID = CCrmCatalog::GetCatalogTypeID();
$arProp = null;

$arUserTypeList = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'edit');
\Bitrix\Main\Type\Collection::sortByColumn($arUserTypeList, array('DESCRIPTION' => SORT_STRING));

if($propID > 0)
{
	$dbRes = CIBlockProperty::GetByID($propID, $iblockID);
	if (is_object($dbRes))
		$arProp = $dbRes->Fetch();
	unset($dbRes);
	if(!is_array($arProp)
		|| (isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE'])
			&& !array_key_exists($arProp['USER_TYPE'], $arUserTypeList)))
	{
		ShowError(GetMessage('CRM_PRODUCTPROP_NOT_FOUND'));
		return;
	}
}
$arResult['PROP_ID'] = $propID;
$arResult['IBLOCK_ID'] = $iblockID;

$arResult['FORM_ID'] = 'CRM_PRODUCTPROP_EDIT_FORM';
$arResult['GRID_ID'] = 'CRM_PRODUCTPROP_EDIT_GRID';
$arResult['BACK_URL'] = CComponentEngine::MakePathFromTemplate(
	$arParams['PATH_TO_PRODUCTPROPS_LIST'],
	array()
);

/*$bFullForm = isset($_REQUEST["IBLOCK_ID"]) && isset($_REQUEST["ID"]);*/
/*$bSectionPopup = isset($_REQUEST["return_url"]) && ($_REQUEST["return_url"] === "section_edit");*/
$bReload = isset($_REQUEST["action"]) && $_REQUEST["action"] === "reload";

define('DEF_LIST_VALUE_COUNT',5);

function __AddListValueIDCell($intPropID,$arPropInfo)
{
	return (0 < intval($intPropID) ? $intPropID : '&nbsp;');
}

function __AddListValueXmlIDCell($intPropID,$arPropInfo)
{
	return '<input type="text" name="PROPERTY_VALUES['.$intPropID.'][XML_ID]" id="PROPERTY_VALUES_XML_'.$intPropID.'" value="'.htmlspecialcharsbx($arPropInfo['XML_ID']).'" size="15" maxlength="200" style="width:90%">';
}

function __AddListValueValueCell($intPropID,$arPropInfo)
{
	return '<input type="text" name="PROPERTY_VALUES['.$intPropID.'][VALUE]" id="PROPERTY_VALUES_VALUE_'.$intPropID.'" value="'.htmlspecialcharsbx($arPropInfo['VALUE']).'" size="35" maxlength="255" style="width:90%">';
}

function __AddListValueSortCell($intPropID,$arPropInfo)
{
	return '<input type="text" name="PROPERTY_VALUES['.$intPropID.'][SORT]" id="PROPERTY_VALUES_SORT_'.$intPropID.'" value="'.intval($arPropInfo['SORT']).'" size="5" maxlength="11">';
}

function __AddListValueDefCell($intPropID,$arPropInfo)
{
	return '<input type="'.('Y' == $arPropInfo['MULTIPLE'] ? 'checkbox' : 'radio').'" name="PROPERTY_VALUES_DEF'.('Y' == $arPropInfo['MULTIPLE'] ? '[]' : '').'" id="PROPERTY_VALUES_DEF_'.$arPropInfo['ID'].'" value="'.$arPropInfo['ID'].'" '.('Y' == $arPropInfo['DEF'] ? 'checked="checked"' : '').'>';
}

function __AddListValueRow($intPropID, $arPropInfo)
{
	return
		'<tr>'.PHP_EOL.
		"\t".'<td class="bx-digit-cell bx-left">'.__AddListValueIDCell($intPropID,$arPropInfo).'</td>'.PHP_EOL.
		"\t".'<td>'.__AddListValueXmlIDCell($intPropID,$arPropInfo).'</td>'.PHP_EOL.
		"\t".'<td>'.__AddListValueValueCell($intPropID,$arPropInfo).'</td>'.PHP_EOL.
		"\t".'<td style="text-align:center">'.__AddListValueSortCell($intPropID,$arPropInfo).'</td>'.PHP_EOL.
		"\t".'<td class="bx-right" style="text-align:center">'.__AddListValueDefCell($intPropID,$arPropInfo).'</td>'.PHP_EOL.
		'</tr>'.PHP_EOL;
}

$arDisabledPropFields = array(
	'ID',
	'IBLOCK_ID',
	'TIMESTAMP_X',
	'TMP_ID',
	'VERSION',
);

$arDefPropInfo = array(
	'ID' => 0,
	'IBLOCK_ID' => 0,
	'FILE_TYPE' => '',
	'LIST_TYPE' => 'L',
	'ROW_COUNT' => '1',
	'COL_COUNT' => '30',
	'LINK_IBLOCK_ID' => '0',
	'DEFAULT_VALUE' => '',
	'USER_TYPE_SETTINGS' => false,
	'WITH_DESCRIPTION' => '',
	'SEARCHABLE' => '',
	'FILTRABLE' => '',
	'ACTIVE' => 'Y',
	'MULTIPLE_CNT' => '5',
	'XML_ID' => '',
	'PROPERTY_TYPE' => 'S',
	'NAME' => '',
	'HINT' => '',
	'USER_TYPE' => '',
	'MULTIPLE' => 'N',
	'IS_REQUIRED' => 'N',
	'SORT' => '500',
	'CODE' => '',
	'SHOW_DEL' => 'N',
	'VALUES' => false,
	'SECTION_PROPERTY' => /*$bSectionPopup? "N": */"Y",
	'SMART_FILTER' => 'N',
);

$arHiddenPropFields = array(
	'IBLOCK_ID',
	'FILE_TYPE',
	'LIST_TYPE',
	'ROW_COUNT',
	'COL_COUNT',
	'LINK_IBLOCK_ID',
	'DEFAULT_VALUE',
	'USER_TYPE_SETTINGS',
	'WITH_DESCRIPTION',
	'SEARCHABLE',
	'FILTRABLE',
	'MULTIPLE_CNT',
	'HINT',
	'XML_ID',
	'VALUES',
	'SECTION_PROPERTY',
	'SMART_FILTER',
);

$bVarsFromForm = $bReload;
$message = false;
$strWarning = "";
$errMsg = "";
$errMsgDirValues = "";

if(check_bitrix_sessid())
{
	if($_SERVER['REQUEST_METHOD'] == 'POST'
		&& isset($_POST['PROPERTY_DIRECTORY_VALUES'])
		&& is_array($_POST['PROPERTY_DIRECTORY_VALUES'])
		&& CModule::IncludeModule('highloadblock'))
	{
		$highBlockID = 0;
		if(isset($_POST["HLB_NEW_TITLE"]) && $_POST["PROPERTY_USER_TYPE_SETTINGS"]["TABLE_NAME"] == '-1')
		{
			$highBlockName = trim($_POST["HLB_NEW_TITLE"]);
			if($highBlockName == '')
			{
				$errMsgDirValues .= GetMessage("CRM_PRODUCT_PE_HBLOCK_NAME_IS_ABSENT").'<br>';
			}
			else
			{
				$highBlockName = mb_strtoupper(mb_substr($highBlockName, 0, 1)).mb_substr($highBlockName, 1);
				if(!preg_match('/^[A-Z][A-Za-z0-9]*$/', $highBlockName))
				{
					$errMsgDirValues .= GetMessage("CRM_PRODUCT_PE_HBLOCK_NAME_IS_INVALID").'<br>';
				}
				else
				{
					$data = array(
						'NAME' => $highBlockName,
						'TABLE_NAME' => 'b_'.mb_strtolower($_POST["HLB_NEW_TITLE"])
					);

					$result = Bitrix\Highloadblock\HighloadBlockTable::add($data);

					$highBlockID = $result->getId();
					$_POST["PROPERTY_USER_TYPE_SETTINGS"]["TABLE_NAME"] = $data['TABLE_NAME'];
					$arFieldsName = $_POST['PROPERTY_DIRECTORY_VALUES'][0];
					$arFieldsName['UF_DEF'] = '';
					$arFieldsName['UF_FILE'] = '';
					$obUserField = new CUserTypeEntity();
					$intSortStep = 100;
					foreach($arFieldsName as $fieldName => $fieldValue)
					{
						if ('UF_DELETE' == $fieldName)
							continue;

						$fieldMandatory = 'N';
						switch($fieldName)
						{
							case 'UF_NAME':
							case 'UF_XML_ID':
								$fieldType = 'string';
								$fieldMandatory = 'Y';
								break;
							case 'UF_LINK':
							case 'UF_DESCRIPTION':
							case 'UF_FULL_DESCRIPTION':
								$fieldType = 'string';
								break;
							case 'UF_SORT':
								$fieldType = 'integer';
								break;
							case 'UF_FILE':
								$fieldType = 'file';
								break;
							case 'UF_DEF':
								$fieldType = 'boolean';
								break;
							default:
								$fieldType = 'string';
						}
						$arUserField = array(
							"ENTITY_ID" => "HLBLOCK_".$highBlockID,
							"FIELD_NAME" => $fieldName,
							"USER_TYPE_ID" => $fieldType,
							"XML_ID" => "",
							"SORT" => $intSortStep,
							"MULTIPLE" => "N",
							"MANDATORY" => $fieldMandatory,
							"SHOW_FILTER" => "N",
							"SHOW_IN_LIST" => "Y",
							"EDIT_IN_LIST" => "Y",
							"IS_SEARCHABLE" => "N",
							"SETTINGS" => array(),
						);
						if(isset($_POST['PROPERTY_USER_TYPE_SETTINGS']['LANG'][$fieldName]))
						{
							$arUserField["EDIT_FORM_LABEL"] = $arUserField["LIST_COLUMN_LABEL"] = $arUserField["LIST_FILTER_LABEL"] = array(LANGUAGE_ID => $_POST['PROPERTY_USER_TYPE_SETTINGS']['LANG'][$fieldName]);
						}
						$obUserField->Add($arUserField);
						$intSortStep += 100;
					}
				}
			}
		}
		if (empty($errMsgDirValues))
		{
			$arImageResult = array();
			if(isset($_FILES['PROPERTY_DIRECTORY_VALUES']) && is_array($_FILES['PROPERTY_DIRECTORY_VALUES']))
				CFile::ConvertFilesToPost($_FILES['PROPERTY_DIRECTORY_VALUES'], $arImageResult);
			if($_POST["PROPERTY_USER_TYPE_SETTINGS"]["TABLE_NAME"] == '-1' && isset($result) && $result->isSuccess())
			{
				$hlblock = Bitrix\Highloadblock\HighloadBlockTable::getById($highBlockID)->fetch();
			}
			else
			{
				$hlblock = Bitrix\Highloadblock\HighloadBlockTable::getList(array("filter" => array("TABLE_NAME" => $_POST["PROPERTY_USER_TYPE_SETTINGS"]["TABLE_NAME"])))->fetch();
			}
			$entity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
			$entityDataClass = $entity->getDataClass();
			$fieldsList = $entityDataClass::getMap();
			if (count($fieldsList) == 1 && isset($fieldsList['ID']))
			{
				$fieldsList = $entityDataClass::getEntity()->getFields();
			}

			foreach($_POST['PROPERTY_DIRECTORY_VALUES'] as $dirKey => $arDirValue)
			{
				if(isset($arDirValue["UF_DELETE"]))
				{
					if($arDirValue["UF_DELETE"] === 'Y')
						if(isset($arDirValue["ID"]) && intval($arDirValue["ID"]) > 0)
						{
							$entityDataClass::delete($arDirValue["ID"]);
							continue;
						}
					unset($arDirValue["UF_DELETE"]);
				}
				if(!is_array($arDirValue) || !isset($arDirValue['UF_NAME']) || '' == trim($arDirValue['UF_NAME']))
					continue;
				if((isset($arImageResult[$dirKey]["FILE"]) && is_array($arImageResult[$dirKey]["FILE"]) && $arImageResult[$dirKey]["FILE"]['name'] != '') || (isset($_POST['PROPERTY_DIRECTORY_VALUES_del'][$dirKey]["FILE"]) && $_POST['PROPERTY_DIRECTORY_VALUES_del'][$dirKey]["FILE"] == 'Y'))
					$arDirValue['UF_FILE'] = $arImageResult[$dirKey]["FILE"];

				if($arDirValue["ID"] == $_POST['PROPERTY_VALUES_DEF'])
					$arDirValue['UF_DEF'] = true;
				else
					$arDirValue['UF_DEF'] = false;
				if(!isset($arDirValue["UF_XML_ID"]) || $arDirValue["UF_XML_ID"] == '')
					$arDirValue['UF_XML_ID'] = randString(8);


				if ($_POST["PROPERTY_USER_TYPE_SETTINGS"]["TABLE_NAME"] == '-1' && isset($result) && $result->isSuccess())
				{
					$entityDataClass::add($arDirValue);
				}
				else
				{
					if (isset($arDirValue["ID"]) && $arDirValue["ID"] > 0)
					{
						$rsData = $entityDataClass::getList(array());
						while($arData = $rsData->fetch())
						{
							$arAddField = array();
							if(!isset($arData["UF_DESCRIPTION"]))
							{
								$arAddField[] = 'UF_DESCRIPTION';
							}
							if(!isset($arData["UF_FULL_DESCRIPTION"]))
							{
								$arAddField[] = 'UF_FULL_DESCRIPTION';
							}
							$obUserField = new CUserTypeEntity();
							foreach($arAddField as $addField)
							{
								$arUserField = array(
									"ENTITY_ID" => "HLBLOCK_".$hlblock["ID"],
									"FIELD_NAME" => $addField,
									"USER_TYPE_ID" => 'string',
									"XML_ID" => "",
									"SORT" => 100,
									"MULTIPLE" => "N",
									"MANDATORY" => "N",
									"SHOW_FILTER" => "N",
									"SHOW_IN_LIST" => "Y",
									"EDIT_IN_LIST" => "Y",
									"IS_SEARCHABLE" => "N",
									"SETTINGS" => array(),
								);
								if(isset($_POST['PROPERTY_USER_TYPE_SETTINGS']['LANG'][$addField]))
								{
									$arUserField["EDIT_FORM_LABEL"] = $arUserField["LIST_COLUMN_LABEL"] = $arUserField["LIST_FILTER_LABEL"] = array(LANGUAGE_ID => $_POST['PROPERTY_USER_TYPE_SETTINGS']['LANG'][$addField]);
								}
								$obUserField->Add($arUserField);
							}
							if($arDirValue["ID"] == $arData["ID"])
							{
								unset($arDirValue["ID"]);
								$dirValueKeys = array_keys($arDirValue);
								foreach ($dirValueKeys as $oneKey)
								{
									if (!isset($fieldsList[$oneKey]))
										unset($arDirValue[$oneKey]);
								}
								if (isset($oneKey))
									unset($oneKey);
								if (!empty($arDirValue))
								{
									$entityDataClass::update($arData["ID"], $arDirValue);
								}
							}
						}
					}
					else
					{
						if (array_key_exists("ID", $arDirValue))
							unset($arDirValue["ID"]);
						$dirValueKeys = array_keys($arDirValue);
						foreach ($dirValueKeys as $oneKey)
						{
							if (!isset($fieldsList[$oneKey]))
								unset($arDirValue[$oneKey]);
						}
						if (isset($oneKey))
							unset($oneKey);
						if (!empty($arDirValue))
						{
							$entityDataClass::add($arDirValue);
						}
					}
				}
			}
		}
	}

	$arListValues = array();
	if ($_SERVER['REQUEST_METHOD'] == 'POST'
		&& isset($_POST['PROPERTY_VALUES'])
		&& is_array($_POST['PROPERTY_VALUES']))
	{
		$boolDefCheck = false;
		if ('Y' == $_POST['PROPERTY_MULTIPLE'])
		{
			$boolDefCheck = (isset($_POST['PROPERTY_VALUES_DEF']) && is_array($_POST['PROPERTY_VALUES_DEF']));
		}
		else
		{
			$boolDefCheck = isset($_POST['PROPERTY_VALUES_DEF']);
		}
		$intNewKey = 0;
		foreach ($_POST['PROPERTY_VALUES'] as $key => $arValue)
		{
			if (!is_array($arValue) || !isset($arValue['VALUE']) || '' == trim($arValue['VALUE']))
				continue;
			$arListValues[(0 < intval($key) ? $key : 'n'.$intNewKey)] = array(
				'ID' => (0 < intval($key) ? $key : 'n'.$intNewKey),
				'VALUE' => strval($arValue['VALUE']),
				'XML_ID' => (isset($arValue['XML_ID']) ? strval($arValue['XML_ID']) : ''),
				'SORT' => (isset($arValue['SORT']) ? intval($arValue['SORT']) : 500),
				'DEF' => ($boolDefCheck ?
					('Y' == $_POST['PROPERTY_MULTIPLE'] ?
						(in_array($key, $_POST['PROPERTY_VALUES_DEF']) ? 'Y' : 'N') :
						($key == $_POST['PROPERTY_VALUES_DEF'] ? 'Y' : 'N')) :
					'N'),
			);
			if (0 >= intval($key))
				$intNewKey++;
		}
	}

	if(!$bReload && $_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['save']) || isset($_POST['apply'])))
	{
		$arFields = array(
			"ACTIVE" => $_POST["PROPERTY_ACTIVE"],
			"IBLOCK_ID" => $_POST["IBLOCK_ID"],
			"LINK_IBLOCK_ID" => $_POST["PROPERTY_LINK_IBLOCK_ID"],
			"NAME" => $_POST["PROPERTY_NAME"],
			"SORT" => $_POST["PROPERTY_SORT"],
			"CODE" => $_POST["PROPERTY_CODE"],
			"MULTIPLE" => $_POST["PROPERTY_MULTIPLE"],
			"IS_REQUIRED" => $_POST["PROPERTY_IS_REQUIRED"],
			"SEARCHABLE" => $_POST["PROPERTY_SEARCHABLE"],
			"FILTRABLE" => $_POST["PROPERTY_FILTRABLE"],
			"WITH_DESCRIPTION" => $_POST["PROPERTY_WITH_DESCRIPTION"],
			"MULTIPLE_CNT" => $_POST["PROPERTY_MULTIPLE_CNT"],
			"HINT" => $_POST["PROPERTY_HINT"],
			"ROW_COUNT" => $_POST["PROPERTY_ROW_COUNT"],
			"COL_COUNT" => $_POST["PROPERTY_COL_COUNT"],
			"DEFAULT_VALUE" => $_POST["PROPERTY_DEFAULT_VALUE"],
			"LIST_TYPE" => $_POST["PROPERTY_LIST_TYPE"],
			"USER_TYPE_SETTINGS" => $_POST["PROPERTY_USER_TYPE_SETTINGS"],
			"FILE_TYPE" => $_POST["PROPERTY_FILE_TYPE"],
		);

		if(isset($_POST["PROPERTY_SMART_FILTER"]))
			$arFields["SMART_FILTER"] = $_POST["PROPERTY_SMART_FILTER"];

		if(isset($_POST["PROPERTY_SECTION_PROPERTY"]))
			$arFields["SECTION_PROPERTY"] = $_POST["PROPERTY_SECTION_PROPERTY"];
		/*elseif($bSectionPopup)
			$arFields["SECTION_PROPERTY"] = "N";*/

		if (isset($_POST["PROPERTY_PROPERTY_TYPE"]))
		{
			if(mb_strpos($_POST["PROPERTY_PROPERTY_TYPE"], ":"))
			{
				list($arFields["PROPERTY_TYPE"], $arFields["USER_TYPE"]) = explode(':', $_POST["PROPERTY_PROPERTY_TYPE"], 2);
			}
			else
			{
				$arFields["PROPERTY_TYPE"] = $_POST["PROPERTY_PROPERTY_TYPE"];
				$arFields["USER_TYPE"] = "";
			}
		}

		if(!empty($arListValues))
			$arFields["VALUES"] = $arListValues;

		if (COption::GetOptionString("iblock", "show_xml_id", "N")=="Y")
			$arFields["XML_ID"] = $_POST["PROPERTY_XML_ID"];

		if(CIBlock::GetArrayByID($arFields["IBLOCK_ID"], "SECTION_PROPERTY") === "N")
		{
			if($arFields["SECTION_PROPERTY"] === "N" || $arFields["SMART_FILTER"] === "Y")
			{
				$ib = new CIBlock;
				$ib->Update($arFields["IBLOCK_ID"], array("SECTION_PROPERTY" => "Y"));
			}
		}

		$ibp = new CIBlockProperty;
		if($propID > 0)
		{
			$arFields['PROPERTY_TYPE'] = $arProp['PROPERTY_TYPE'];
			$arFields['USER_TYPE'] = $arProp['USER_TYPE'];
			if ($arFields['PROPERTY_TYPE'].':'.$arFields['USER_TYPE'] === 'S:map_yandex')
				$arFields['MULTIPLE'] = $arProp['MULTIPLE'];
			$res = $ibp->Update($propID, $arFields, true);
		}
		else
		{
			if ($arFields['PROPERTY_TYPE'].':'.$arFields['USER_TYPE'] === 'S:map_yandex')
				$arFields['MULTIPLE'] = 'N';
			$propID = intval($ibp->Add($arFields));
		}

		if($propID <= 0)
		{
			$errMsg .= $ibp->LAST_ERROR;
			$bVarsFromForm = true;
			if($e = $APPLICATION->GetException())
				$errMsg .= $e->GetString().'<br>';
		}
		else
		{
			if (isset($_POST['apply']))
				LocalRedirect(CComponentEngine::makePathFromTemplate($arParams['PATH_TO_PRODUCTPROPS_EDIT'], array('prop_id' => $propID)));
			else
				LocalRedirect(CComponentEngine::makePathFromTemplate($arParams['PATH_TO_PRODUCTPROPS_LIST']));
		}
	}
	elseif ($_SERVER['REQUEST_METHOD'] == 'GET' &&  isset($_GET['delete']))
	{
		CIBlockProperty::Delete($propID);
		LocalRedirect(CComponentEngine::makePathFromTemplate($arParams['PATH_TO_PRODUCTPROPS_LIST']));
	}
}

$arResult['ERR_MSG'] = $errMsg.$errMsgDirValues;

if($bVarsFromForm)
{
	$arProperty = array(
		"ID" => $propID,
		"ACTIVE" => $_POST["PROPERTY_ACTIVE"],
		"IBLOCK_ID" => $iblockID,
		"NAME" => $_POST["PROPERTY_NAME"],
		"SORT" => $_POST["PROPERTY_SORT"],
		"CODE" => $_POST["PROPERTY_CODE"],
		"MULTIPLE" => $_POST["PROPERTY_MULTIPLE"],
		"IS_REQUIRED" => $_POST["PROPERTY_IS_REQUIRED"],
		"SEARCHABLE" => $_POST["PROPERTY_SEARCHABLE"],
		"FILTRABLE" => $_POST["PROPERTY_FILTRABLE"],
		"WITH_DESCRIPTION" => $_POST["PROPERTY_WITH_DESCRIPTION"],
		"MULTIPLE_CNT" => $_POST["PROPERTY_MULTIPLE_CNT"],
		"HINT" => $_POST["PROPERTY_HINT"],
		"SECTION_PROPERTY" => $_POST["PROPERTY_SECTION_PROPERTY"],
		"SMART_FILTER" => $_POST["PROPERTY_SMART_FILTER"],
		"ROW_COUNT" => $_POST["PROPERTY_ROW_COUNT"],
		"COL_COUNT" => $_POST["PROPERTY_COL_COUNT"],
		"DEFAULT_VALUE" => $_POST["PROPERTY_DEFAULT_VALUE"],
		"FILE_TYPE" => $_POST["PROPERTY_FILE_TYPE"],
	);

	if (isset($_POST["PROPERTY_PROPERTY_TYPE"]))
	{
		if(mb_strpos($_POST["PROPERTY_PROPERTY_TYPE"], ":"))
		{
			list($arProperty["PROPERTY_TYPE"], $arProperty["USER_TYPE"]) = explode(':', $_POST["PROPERTY_PROPERTY_TYPE"], 2);
		}
		else
		{
			$arProperty["PROPERTY_TYPE"] = $_POST["PROPERTY_PROPERTY_TYPE"];
		}
	}

	if(!empty($arListValues))
		$arProperty["VALUES"] = $arListValues;
}
elseif(is_array($arProp))
{
	$arProperty = $arProp;
	if ($arProperty['PROPERTY_TYPE'] == "L")
	{
		$arProperty['VALUES'] = array();
		$rsLists = CIBlockProperty::GetPropertyEnum($arProperty['ID'],array('SORT' => 'ASC','ID' => 'ASC'));
		while($res = $rsLists->Fetch())
		{
			$arProperty['VALUES'][$res["ID"]] = array(
				'ID' => $res["ID"],
				'VALUE' => $res["VALUE"],
				'SORT' => $res['SORT'],
				'XML_ID' => $res["XML_ID"],
				'DEF' => $res['DEF'],
			);
		}
	}
	$arPropLink = CIBlockSectionPropertyLink::GetArray($iblockID, 0);
	if(isset($arPropLink[$arProperty["ID"]]))
	{
		$arProperty["SECTION_PROPERTY"] = "Y";
		$arProperty["SMART_FILTER"] = $arPropLink[$arProperty["ID"]]["SMART_FILTER"];
	}
	else
	{
		$arProperty["SECTION_PROPERTY"] = "N";
		$arProperty["SMART_FILTER"] = "N";
	}
}
else
{
	$arProperty = $arDefPropInfo;
	$arProperty["IBLOCK_ID"] = $iblockID;
}

$arProperty['USER_TYPE'] = trim($arProperty['USER_TYPE']);
$arResult['PROPERTY'] = $arProperty;

if ('L' == $arProperty['PROPERTY_TYPE'])
	$arDefPropInfo['MULTIPLE'] = $arProperty['MULTIPLE'];

$arResult['LIST_VALUE_ID_CELL'] = __AddListValueIDCell('ntmp_xxx',$arDefPropInfo);
$arResult['LIST_VALUE_XMLID_CELL'] = __AddListValueXmlIDCell('ntmp_xxx',$arDefPropInfo);
$arResult['LIST_VALUE_VALUE_CELL'] = __AddListValueValueCell('ntmp_xxx',$arDefPropInfo);
$arResult['LIST_VALUE_SORT_CELL'] = __AddListValueSortCell('ntmp_xxx',$arDefPropInfo);
$arResult['LIST_VALUE_DEF_CELL'] = __AddListValueDefCell('ntmp_xxx',$arDefPropInfo);

$arTypesList = array(
	"S" => GetMessage("CRM_PRODUCT_PE_PROP_TYPE_S"),
	"N" => GetMessage("CRM_PRODUCT_PE_PROP_TYPE_N"),
	"L" => GetMessage("CRM_PRODUCT_PE_PROP_TYPE_L"),
	"F" => GetMessage("CRM_PRODUCT_PE_PROP_TYPE_F"),
	/*"G" => GetMessage("CRM_PRODUCT_PE_PROP_TYPE_G"),*/
	"E" => GetMessage("CRM_PRODUCT_PE_PROP_TYPE_E"),
);

$arUserType = ('' != $arProperty['USER_TYPE'] ? CCrmProductPropsHelper::GetPropsTypesByOperations($arProperty['USER_TYPE']) : array());

$arPropertyFields = array();
$userTypeSettingsHTML = "";
if(isset($arUserType["GetSettingsHTML"]))
	$userTypeSettingsHTML = call_user_func_array($arUserType["GetSettingsHTML"],
		array(
			$arProperty,
			array(
				"NAME"=>"PROPERTY_USER_TYPE_SETTINGS",
			),
			&$arPropertyFields,
		)
	);
$arResult['PROPERTY_TYPE'] = $arProperty['PROPERTY_TYPE'].($arProperty['USER_TYPE']? ':'.$arProperty['USER_TYPE']: '');




$arResult['FIELDS'] = array();
$arResult['HIDDEN_FIELDS_HTML'] = '';

$customFieldHTML =
	'<input type="hidden" name="PROPERTY_FILE_TYPE" value="'.htmlspecialcharsbx($arProperty['FILE_TYPE']).'">'.
	(0 < intval($arProperty['ID']) ? $arProperty['ID'] : GetMessage("CRM_PRODUCT_PE_PROP_NEW"));
$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'PROP_ID',
	'name' => GetMessage('CRM_PRODUCTPROP_FIELD_ID'),
	'params' => array('size' => 10),
	'value' => $customFieldHTML,
	'type' => 'custom'
);

if (intval($arProperty['ID']) <= 0)
{
	$boolUserPropExist = false/*!empty($arUserTypeList)*/;
	$customFieldHTML = '<input type="hidden" id="PROPERTY_PROPERTY_TYPE" name="PROPERTY_PROPERTY_TYPE" value="' . htmlspecialcharsbx($arResult['PROPERTY_TYPE']) . '">';
	$customFieldHTML .= '<select name="PROPERTY_PROPERTY_TYPE" onchange="reloadForm();">';
	if ($boolUserPropExist)
		$customFieldHTML .= '<optgroup label="' . GetMessage('CRM_PRODUCT_PE_PROPERTY_BASE_TYPE_GROUP') . '">';
	foreach ($arTypesList as $k => $v)
		$customFieldHTML .= '<option value="' . $k . '" ' . ($arResult['PROPERTY_TYPE'] === $k ? " selected" : '') . '>' . $v . '</option>';
	if ($boolUserPropExist)
		$customFieldHTML .= '</optgroup><optgroup label="' . GetMessage('CRM_PRODUCT_PE_PROPERTY_USER_TYPE_GROUP') . '">';
	foreach ($arUserTypeList as $ar)
		$customFieldHTML .= '<option value="' . htmlspecialcharsbx($ar['PROPERTY_TYPE'] . ':' . $ar['USER_TYPE']) .
			'" ' . ($arResult['PROPERTY_TYPE'] === ($ar['PROPERTY_TYPE'] . ':' . $ar['USER_TYPE']) ? ' selected' : '') .
			'>' . htmlspecialcharsbx($ar['DESCRIPTION']) . '</option>';
	if ($boolUserPropExist)
		$customFieldHTML .= '</optgroup>';
	$customFieldHTML .= '</select>';
	if ($arResult['PROPERTY_TYPE'] === 'N' && $arResult['USER_TYPE'] == '')
	{
		$hintHTML = htmlspecialcharsbx(GetMessage('CRM_PRODUCT_PE_PROPERTY_TYPE_HINT_N'));
		$hintHTML = str_replace(
			'#EXAMPLE#',
			'<span class="bold">'.htmlspecialcharsbx(GetMessage('CRM_PRODUCT_PE_PROPERTY_TYPE_HINT_N_E')).'</span>',
			$hintHTML
		);
		$hintHTML .= ' '.htmlspecialcharsbx(GetMessage('CRM_PRODUCT_PE_PROPERTY_TYPE_HINT_N1'));
		$customFieldHTML .= '<span class = "crm-dup-control-type-info">'.$hintHTML.'</span>';
		unset($hintHTML);
	}
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'PROPERTY_PROPERTY_TYPE',
		'name' => GetMessage('CRM_PRODUCT_PE_PROPERTY_TYPE'),
		'value' => $customFieldHTML,
		'type' => 'custom'
	);
	unset($customFieldHTML);
}
else
{
	$propertyTypeText = '';
	foreach ($arTypesList as $k => $v)
	{
		if ($arResult['PROPERTY_TYPE'] === $k)
		{
			$propertyTypeText = $v;
			break;
		}
	}
	foreach ($arUserTypeList as $ar)
	{
		if ($arResult['PROPERTY_TYPE'] === ($ar['PROPERTY_TYPE'] . ':' . $ar['USER_TYPE']))
		{
			$propertyTypeText = $ar['DESCRIPTION'];
			break;
		}
	}
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'PROPERTY_PROPERTY_TYPE',
		'name' => GetMessage('CRM_PRODUCT_PE_PROPERTY_TYPE'),
		'value' => $propertyTypeText,
		'type' => 'label'
	);
	unset($propertyTypeText);
}

$showKeyExist = isset($arPropertyFields['SHOW']) && !empty($arPropertyFields['SHOW']) && is_array($arPropertyFields['SHOW']);
$hideKeyExist = isset($arPropertyFields['HIDE']) && !empty($arPropertyFields['HIDE']) && is_array($arPropertyFields['HIDE']);

$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'PROPERTY_ACTIVE',
	'name' => GetMessage('CRM_PRODUCT_PE_PROP_ACT'),
	'value' => $arProperty['ACTIVE'],
	'type' => 'checkbox'
);

$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'PROPERTY_SORT',
	'name' => GetMessage('CRM_PRODUCT_PE_PROP_SORT_DET'),
	'value' => intval($arProperty['SORT']),
	'type' => 'text',
	'params' => array('size' => '3', 'maxlength' => '10')
);

$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'PROPERTY_NAME',
	'name' => GetMessage('CRM_PRODUCT_PE_PROP_NAME_DET'),
	'value' => htmlspecialcharsbx($arProperty['NAME']),
	'type' => 'text',
	'params' => array('size' => '50', 'maxlength' => '255'),
	'required' => true
);

/*$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'PROPERTY_CODE',
	'name' => GetMessage('CRM_PRODUCT_PE_PROP_CODE_DET'),
	'value' => htmlspecialcharsbx($arProperty['CODE']),
	'type' => 'text',
	'params' => array('size' => '50', 'maxlength' => '50')
);*/

if (COption::GetOptionString('iblock', 'show_xml_id', 'N') === 'Y')
{
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'PROPERTY_XML_ID',
		'name' => GetMessage('CRM_PRODUCT_PE_PROP_EXTERNAL_CODE'),
		'value' => htmlspecialcharsbx($arProperty['XML_ID']),
		'type' => 'text',
		'params' => array('size' => '50', 'maxlength' => '50')
	);
}

$bShow = true;
if($showKeyExist && in_array('MULTIPLE', $arPropertyFields['SHOW']))
	$bShow = true;
elseif($hideKeyExist && in_array('MULTIPLE', $arPropertyFields['HIDE']))
	$bShow = false;
if ($bShow)
{
	if ($arProperty['PROPERTY_TYPE'].':'.$arProperty['USER_TYPE'] === 'S:map_yandex')
	{
		$arResult['FIELDS']['tab_params'][] = array(
			'id' => 'PROPERTY_MULTIPLE',
			'name' => GetMessage('CRM_PRODUCT_PE_PROP_MULTIPLE'),
			'value' => GetMessage($arProperty['MULTIPLE'] === 'Y' ? 'MAIN_YES' : 'MAIN_NO'),
			'type' => 'label'
		);
	}
	else
	{
		$arResult['FIELDS']['tab_params'][] = array(
			'id' => 'PROPERTY_MULTIPLE',
			'name' => GetMessage('CRM_PRODUCT_PE_PROP_MULTIPLE'),
			'value' => $arProperty['MULTIPLE'],
			'type' => 'checkbox'
		);
	}
}
elseif (isset($arPropertyFields['SET']['MULTIPLE']))
{
	$arResult['HIDDEN_FIELDS_HTML'] .=
		'<input type="hidden" name="PROPERTY_MULTIPLE" value="'.htmlspecialcharsbx($arPropertyFields['SET']['MULTIPLE']).'">';
}

$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'PROPERTY_IS_REQUIRED',
	'name' => GetMessage('CRM_PRODUCT_PE_PROP_IS_REQUIRED'),
	'value' => $arProperty['IS_REQUIRED'],
	'type' => 'checkbox'
);

if (CCrmProductPropsHelper::CanBeFiltered($arUserTypeList, $arProperty))
{
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'PROPERTY_FILTRABLE',
		'name' => GetMessage('CRM_PRODUCT_PE_PROP_FILTRABLE'),
		'value' => $arProperty['FILTRABLE'],
		'type' => 'checkbox'
	);
}

$bShow = false;
if($showKeyExist && in_array('SEARCHABLE', $arPropertyFields['SHOW']))
	$bShow = true;
elseif($hideKeyExist && in_array('SEARCHABLE', $arPropertyFields['HIDE']))
	$bShow = false;
elseif('E' == $arProperty['PROPERTY_TYPE'] || 'G' == $arProperty['PROPERTY_TYPE'])
	$bShow = false;
if ($bShow)
{
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'PROPERTY_SEARCHABLE',
		'name' => GetMessage('CRM_PRODUCT_PE_PROP_SEARCHABLE'),
		'value' => $arProperty['SEARCHABLE'],
		'type' => 'checkbox'
	);
}
else if (isset($arPropertyFields['SET']['SEARCHABLE']))
{
	$arResult['HIDDEN_FIELDS_HTML'] .=
		'<input type="hidden" name="PROPERTY_SEARCHABLE" value="'.htmlspecialcharsbx($arPropertyFields['SET']['SEARCHABLE']).'">';
}

$bShow = false;
if($showKeyExist && in_array('FILTRABLE', $arPropertyFields['SHOW']))
	$bShow = true;
elseif($hideKeyExist && in_array('FILTRABLE', $arPropertyFields['HIDE']))
	$bShow = false;
elseif($arProperty['PROPERTY_TYPE'] == 'F')
	$bShow = false;
if ($bShow)
{
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'PROPERTY_FILTRABLE',
		'name' => GetMessage('CRM_PRODUCT_PE_PROP_FILTRABLE'),
		'value' => $arProperty['FILTRABLE'],
		'type' => 'checkbox'
	);
}
else if (isset($arPropertyFields['SET']['FILTRABLE']))
{
	$arResult['HIDDEN_FIELDS_HTML'] .=
		'<input type="hidden" name="PROPERTY_FILTRABLE" value="'.htmlspecialcharsbx($arPropertyFields['SET']['FILTRABLE']).'">';
}

$bShow = false;
if ($showKeyExist && in_array('WITH_DESCRIPTION', $arPropertyFields['SHOW']))
	$bShow = true;
elseif ($hideKeyExist && in_array('WITH_DESCRIPTION', $arPropertyFields['HIDE']))
	$bShow = false;
elseif ('L' == $arProperty['PROPERTY_TYPE'] || 'G' == $arProperty['PROPERTY_TYPE'] || 'E' == $arProperty['PROPERTY_TYPE'])
	$bShow = false;
if ($bShow)
{
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'PROPERTY_WITH_DESCRIPTION',
		'name' => GetMessage('CRM_PRODUCT_PE_PROP_WITH_DESC'),
		'value' => $arProperty['WITH_DESCRIPTION'],
		'type' => 'checkbox'
	);
}
else if (isset($arPropertyFields['SET']['WITH_DESCRIPTION']))
{
	$arResult['HIDDEN_FIELDS_HTML'] .=
		'<input type="hidden" name="PROPERTY_WITH_DESCRIPTION" value="'.htmlspecialcharsbx($arPropertyFields['SET']['WITH_DESCRIPTION']).'">';
}

$bShow = false;
if ($showKeyExist && in_array('MULTIPLE_CNT', $arPropertyFields['SHOW']))
	$bShow = true;
elseif ($hideKeyExist && in_array('MULTIPLE_CNT', $arPropertyFields['HIDE']))
	$bShow = false;
elseif ('L' == $arProperty['PROPERTY_TYPE'])
	$bShow = false;
elseif ('F' == $arProperty['PROPERTY_TYPE'])
	$bShow = false;
if ($bShow)
{
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'PROPERTY_MULTIPLE_CNT',
		'name' => GetMessage('CRM_PRODUCT_PE_PROP_MULTIPLE_CNT'),
		'value' => intval($arProperty['MULTIPLE_CNT']),
		'type' => 'text',
		'params' => array('size' => '3')
	);
}
elseif(isset($arPropertyFields['SET']['MULTIPLE_CNT']))
{
	$arResult['HIDDEN_FIELDS_HTML'] .=
		'<input type="hidden" name="PROPERTY_MULTIPLE_CNT" value="'.htmlspecialcharsbx($arPropertyFields['SET']['MULTIPLE_CNT']).'">';
}

/*$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'PROPERTY_HINT',
	'name' => GetMessage('CRM_PRODUCT_PE_PROP_HINT_DET'),
	'value' => htmlspecialcharsbx($arProperty['HINT']),
	'type' => 'text',
	'params' => array('size' => '50', 'maxlength' => '255')
);*/

/*$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'PROPERTY_SECTION_PROPERTY',
	'name' => GetMessage('CRM_PRODUCT_PE_PROP_SECTION_PROPERTY'),
	'value' => $arProperty['SECTION_PROPERTY'],
	'type' => 'checkbox'
);*/

$bShow = false;
if ($showKeyExist && in_array('SMART_FILTER', $arPropertyFields['SHOW']))
	$bShow = true;
elseif ($hideKeyExist && in_array('SMART_FILTER', $arPropertyFields['HIDE']))
	$bShow = false;
elseif($arProperty['PROPERTY_TYPE'] == 'F')
	$bShow = false;
if ($bShow)
{
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'PROPERTY_SMART_FILTER',
		'name' => GetMessage('CRM_PRODUCT_PE_PROP_SMART_FILTER'),
		'value' => $arProperty['SMART_FILTER'],
		'type' => 'checkbox'
	);
}
else if (isset($arPropertyFields['SET']['SMART_FILTER']))
{
	$arResult['HIDDEN_FIELDS_HTML'] .=
		'<input type="hidden" name="PROPERTY_SMART_FILTER" value="'.htmlspecialcharsbx($arPropertyFields['SET']['FILTRABLE']).'">';
}

// PROPERTY_TYPE specific properties
if ('L' == $arProperty['PROPERTY_TYPE'])
{
	/*$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'PROPERTY_LIST_TYPE',
		'name' => GetMessage('CRM_PRODUCT_PE_PROP_APPEARANCE'),
		'items' => array(
			'L' => GetMessage('CRM_PRODUCT_PE_PROP_APPEARANCE_LIST'),
			'C' => GetMessage('CRM_PRODUCT_PE_PROP_APPEARANCE_CHECKBOX')
		),
		'value' => $arProperty['LIST_TYPE'],
		'type' => 'list'
	);*/
	if (empty($arProperty['LIST_TYPE']))
		$arProperty['LIST_TYPE'] = 'L';
	$arResult['HIDDEN_FIELDS_HTML'] .=
		'<input type="hidden" name="PROPERTY_LIST_TYPE" value="'.htmlspecialcharsbx($arProperty['LIST_TYPE']).'">';

	$bShow = true;
	if ($showKeyExist && in_array('ROW_COUNT', $arPropertyFields['SHOW']))
		$bShow = true;
	elseif ($hideKeyExist && in_array('ROW_COUNT', $arPropertyFields['HIDE']))
		$bShow = false;
	if ($bShow)
	{
		$arResult['FIELDS']['tab_params'][] = array(
			'id' => 'PROPERTY_ROW_COUNT',
			'name' => GetMessage('CRM_PRODUCT_PE_PROP_ROW_CNT'),
			'value' => intval($arProperty['ROW_COUNT']),
			'type' => 'text',
			'params' => array('size' => '2', 'maxlength' => '10')
		);
	}
	elseif(	isset($arPropertyFields['SET']['ROW_COUNT']))
	{
		$arResult['HIDDEN_FIELDS_HTML'] .=
			'<input type="hidden" name="PROPERTY_ROW_COUNT" value="'.htmlspecialcharsbx($arPropertyFields['SET']['ROW_COUNT']).'">';
	}

	$customFieldHTML =
		'<div style="width: 70%;">'.PHP_EOL.
		"\t".'<table class="bx-interface-grid" id="list-tbl" cellspacing="0" style="width: 100%;">'.PHP_EOL.
		"\t\t".'<tbody>'.PHP_EOL.
		"\t\t".'<tr class="bx-grid-head">'.PHP_EOL.
		"\t\t\t".'<td class="bx-left">'.GetMessage("CRM_PRODUCT_PE_PROP_LIST_ID").'</td>'.PHP_EOL.
		"\t\t\t".'<td>'.GetMessage("CRM_PRODUCT_PE_PROP_LIST_XML_ID").'</td>'.PHP_EOL.
		"\t\t\t".'<td>'.GetMessage("CRM_PRODUCT_PE_PROP_LIST_VALUE").'</td>'.PHP_EOL.
		"\t\t\t".'<td>'.GetMessage("CRM_PRODUCT_PE_PROP_LIST_SORT").'</td>'.PHP_EOL.
		"\t\t\t".'<td class="bx-right">'.GetMessage("CRM_PRODUCT_PE_PROP_LIST_DEFAULT").'</td>'.PHP_EOL.
		"\t\t".'</tr>'.PHP_EOL;
	if ('Y' != $arProperty['MULTIPLE'])
	{
		$boolDef = true;
		if (isset($arProperty['VALUES']) && is_array($arProperty['VALUES']))
		{
			foreach ($arProperty['VALUES'] as &$arListValue)
			{
				if ('Y' == $arListValue['DEF'])
				{
					$boolDef = false;
					break;
				}
			}
			unset($arListValue);
		}
		$customFieldHTML .=
			"\t\t".'<tr>'.PHP_EOL.
			/*"\t\t\t".'<td class="bx-left">&nbsp;</td>'.PHP_EOL.
			"\t\t\t".'<td>&nbsp;</td>'.PHP_EOL.*/
			"\t\t\t".'<td class="bx-left" colspan="4" style="text-align: center;">'.GetMessage("CRM_PRODUCT_PE_PROP_LIST_DEFAULT_NO").'</td>'.PHP_EOL.
			"\t\t\t".'<td class="bx-right" style="text-align:center"><input type="radio" name="PROPERTY_VALUES_DEF" value="0" '.($boolDef ? ' checked' : '').'> </td>'.PHP_EOL.
			"\t\t".'</tr>'.PHP_EOL;
	}
	$MAX_NEW_ID = 0;
	if (isset($arProperty['VALUES']) && is_array($arProperty['VALUES']))
	{
		foreach ($arProperty['VALUES'] as $intKey => $arListValue)
		{
			$arPropInfo = array(
				'ID' => $intKey,
				'XML_ID' => $arListValue['XML_ID'],
				'VALUE' => $arListValue['VALUE'],
				'SORT' => (0 < intval($arListValue['SORT']) ? intval($arListValue['SORT']) : '500'),
				'DEF' => ('Y' == $arListValue['DEF'] ? 'Y' : 'N'),
				'MULTIPLE' => $arProperty['MULTIPLE'],
			);
			$customFieldHTML .= __AddListValueRow($intKey,$arPropInfo);
		}
		$MAX_NEW_ID = sizeof($arProperty['VALUES']);
	}
	$arResult['MAX_NEW_ID'] = $MAX_NEW_ID;

	$lastRowNum = $MAX_NEW_ID + DEF_LIST_VALUE_COUNT - 1;
	for ($i = $MAX_NEW_ID; $i <= $lastRowNum; $i++)
	{
		$intKey = 'n'.$i;
		$arPropInfo = array(
			'ID' => $intKey,
			'XML_ID' => '',
			'VALUE' => '',
			'SORT' => '500',
			'DEF' => 'N',
			'MULTIPLE' => $arProperty['MULTIPLE'],
		);
		$customFieldHTML .= __AddListValueRow($intKey,$arPropInfo);
	}
	$customFieldHTML .=
		"\t\t".'</tbody>'.PHP_EOL.
		"\t".'</table>'.PHP_EOL.
		"\t".'<div style="width: 100%; text-align: center; margin: 10px 0;">'.PHP_EOL.
		"\t\t".'<input class="adm-btn-big" type="button" id="propedit_add_btn" name="propedit_add" value="'.GetMessage('CRM_PRODUCT_PE_PROP_LIST_MORE').'">'.PHP_EOL.
		"\t".'</div>'.PHP_EOL.
		"\t".'<input type="hidden" name="PROPERTY_CNT" id="PROPERTY_CNT" value="'.($MAX_NEW_ID + DEF_LIST_VALUE_COUNT).'">'.PHP_EOL.
		'</div>'.PHP_EOL;
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'PROPERTY_LIST_VALUES',
		'name' => GetMessage('CRM_PRODUCT_PE_PROP_LIST_VALUES'),
		'value' => $customFieldHTML,
		'type' => 'custom'
	);
}
else if ("F" == $arProperty['PROPERTY_TYPE'])
{
	$bShow = false;
	if ($showKeyExist && in_array('COL_COUNT', $arPropertyFields['SHOW']))
		$bShow = true;
	elseif ($hideKeyExist && in_array('COL_COUNT', $arPropertyFields['HIDE']))
		$bShow = false;
	if ($bShow)
	{
		$arResult['FIELDS']['tab_params'][] = array(
			'id' => 'PROPERTY_COL_COUNT',
			'name' => GetMessage('CRM_PRODUCT_PE_PROP_FILE_TYPES_COL_CNT'),
			'value' => intval($arProperty['COL_COUNT']),
			'type' => 'text',
			'params' => array('size' => '2', 'maxlength' => '10')
		);
	}
	else if (isset($arPropertyFields["SET"]["COL_COUNT"]))
	{
		$arResult['HIDDEN_FIELDS_HTML'] .=
			'<input type="hidden" name="PROPERTY_COL_COUNT" value="'.htmlspecialcharsbx($arPropertyFields['SET']['COL_COUNT']).'">';
	}
	$customFieldHTML =
		'<input id="CURRENT_PROPERTY_FILE_TYPE" type="text"  size="50" maxlength="255" name="PROPERTY_FILE_TYPE" value="'.htmlspecialcharsbx($arProperty['FILE_TYPE']).'" />'.
		'<select onchange="if(this.selectedIndex!=0) document.getElementById(\'CURRENT_PROPERTY_FILE_TYPE\').value=this[this.selectedIndex].value" '.
		'style="width: auto; margin-left: 4px;">'.
		'<option value="-"></option>'.
		'<option value=""'.('' == $arProperty['FILE_TYPE'] ? ' selected' : '').'>'.GetMessage('CRM_PRODUCT_PE_PROP_FILE_TYPES_ANY').'</option>'.
		'<option value="jpg, gif, bmp, png, jpeg, webp"'.('jpg, gif, bmp, png, jpeg, webp' == $arProperty['FILE_TYPE'] ? ' selected' : '').'>'.GetMessage('CRM_PRODUCT_PE_PROP_FILE_TYPES_PIC').'</option>'.
		'<option value="mp3, wav, midi, snd, au, wma"'.('mp3, wav, midi, snd, au, wma' == $arProperty['FILE_TYPE'] ? ' selected' : '').'>'.GetMessage('CRM_PRODUCT_PE_PROP_FILE_TYPES_SOUND').'</option>'.
		'<option value="mpg, avi, wmv, mpeg, mpe, flv"'.('mpg, avi, wmv, mpeg, mpe, flv' == $arProperty['FILE_TYPE'] ? ' selected' : '').'>'.GetMessage('CRM_PRODUCT_PE_PROP_FILE_TYPES_VIDEO').'</option>'.
		'<option value="doc, txt, rtf"'.('doc, txt, rtf' == $arProperty['FILE_TYPE'] ? ' selected' : '').'>'.GetMessage('CRM_PRODUCT_PE_PROP_FILE_TYPES_DOCS').'</option>'.
		'</select>';
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'PROPERTY_FILE_TYPE',
		'name' => GetMessage('CRM_PRODUCT_PE_PROP_FILE_TYPES'),
		'value' => $customFieldHTML,
		'type' => 'custom'
	);
}
else if ("G" == $arProperty['PROPERTY_TYPE'] || "E" == $arProperty['PROPERTY_TYPE'])
{
	$bShow = false;
	if ($showKeyExist && in_array('COL_COUNT', $arPropertyFields['SHOW']))
	{
		$bShow = true;
	}
	if ($bShow)
	{
		$arResult['FIELDS']['tab_params'][] = array(
			'id' => 'PROPERTY_COL_COUNT',
			'name' => GetMessage('CRM_PRODUCT_PE_PROP_FILE_TYPES_COL_CNT'),
			'value' => intval($arProperty['COL_COUNT']),
			'type' => 'text',
			'params' => array('size' => '2', 'maxlength' => '10')
		);
	}
	else if (isset($arPropertyFields['SET']['COL_COUNT']))
	{
		$arResult['HIDDEN_FIELDS_HTML'] .=
			'<input type="hidden" name="PROPERTY_COL_COUNT" value="'.htmlspecialcharsbx($arPropertyFields['SET']['COL_COUNT']).'">';
	}

	$b_f = ($arProperty['PROPERTY_TYPE']=="G" || ($arProperty['PROPERTY_TYPE'] == 'E' && $arProperty['USER_TYPE'] == BT_UT_SKU_CODE) ? array("!ID"=>$iblockID) : array());
	$b_f['TYPE'] = $iblockTypeID;
	$b_f['CHECK_PERMISSIONS'] = 'N';
	/*$customFieldHTML = GetIBlockDropDownList(
		$arProperty['LINK_IBLOCK_ID'],
		"PROPERTY_LINK_IBLOCK_TYPE_ID",
		"PROPERTY_LINK_IBLOCK_ID",
		$b_f,
		'class="adm-detail-iblock-types"',
		'class="adm-detail-iblock-list"'
	);*/
	$res = CCrmCatalog::GetList(array(), array('ID' => $iblockID), false, false, ['NAME']);
	$row = is_object($res) ? $res->Fetch() : null;
	$catalogTitle = '';
	unset($res);
	$catalogTitle = is_array($row) ? $row['NAME'] : '';
	$catalogTitle .= ($catalogTitle <> '' ? ' ' : '').'['.$iblockID.']';
	$customFieldHTML = '<input type="hidden" name="PROPERTY_LINK_IBLOCK_TYPE_ID" value="'.$iblockTypeID.'">'.
		'<input type="hidden" name="PROPERTY_LINK_IBLOCK_ID" value="'.$iblockID.'">'.htmlspecialcharsbx($catalogTitle);
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'PROPERTY_FILE_TYPE',
		'name' => GetMessage('CRM_PRODUCT_PE_PROP_LINK_IBLOCK'),
		'value' => $customFieldHTML,
		'type' => 'custom'
	);
}
else
{
	$bShow = true;
	if ($hideKeyExist && in_array('COL_COUNT', $arPropertyFields['HIDE']))
		$bShow = false;
	elseif ($hideKeyExist && in_array('ROW_COUNT', $arPropertyFields['HIDE']))
		$bShow = false;
	if ("S" == $arProperty['PROPERTY_TYPE'] && (!isset($arProperty['USER_TYPE']) || empty($arProperty['USER_TYPE'])))
	{
		if ($bShow)
		{
			$customFieldHTML =
				'<input type="text" style="width: 40px;" size="2" maxlength="10" name="PROPERTY_ROW_COUNT" value="'.intval($arProperty['ROW_COUNT']).'"> x '.
				'<input type="text" style="width: 40px;" size="2" maxlength="10" name="PROPERTY_COL_COUNT" value="'.intval($arProperty['COL_COUNT']).'">';
			$arResult['FIELDS']['tab_params'][] = array(
				'id' => 'PROPERTY_ROW_COUNT',
				'name' => GetMessage('CRM_PRODUCT_PE_PROP_SIZE'),
				'value' => $customFieldHTML,
				'type' => 'custom'
			);
		}
		else
		{
			$val = '';
			if (isset($arPropertyFields['SET']['ROW_COUNT']))
				$val = htmlspecialcharsbx($arPropertyFields['SET']['ROW_COUNT']);
			else
				$val = intval($arProperty['ROW_COUNT']);
			$arResult['HIDDEN_FIELDS_HTML'] .=
				'<input type="hidden" name="PROPERTY_ROW_COUNT" value="'.$val.'">';

			if(isset($arPropertyFields["SET"]["COL_COUNT"]))
				$val = htmlspecialcharsbx($arPropertyFields["SET"]["COL_COUNT"]);
			else
				$val = intval($arProperty['COL_COUNT']);
			$arResult['HIDDEN_FIELDS_HTML'] .=
				'<input type="hidden" name="PROPERTY_COL_COUNT" value="'.$val.'">';
		}
	}

	$bShow = true;
	if ($hideKeyExist && in_array('DEFAULT_VALUE', $arPropertyFields['HIDE']))
		$bShow = false;
	if ($bShow)
	{
		if(array_key_exists('GetPublicEditHTML', $arUserType))
		{
			$customFieldHTML = call_user_func_array(
				$arUserType['GetPublicEditHTML'],
				array(
					$arProperty,
					array(
						'VALUE'=>$arProperty['DEFAULT_VALUE'],
						'DESCRIPTION'=>''
					),
					array(
						'VALUE'=>'PROPERTY_DEFAULT_VALUE',
						'DESCRIPTION'=>'',
						'FORM_NAME' => 'form_'.$arResult['FORM_ID']
					),
				)
			);
			$arResult['FIELDS']['tab_params'][] = array(
				'id' => 'PROPERTY_DEFAULT_VALUE',
				'name' => GetMessage('CRM_PRODUCT_PE_PROP_DEFAULT'),
				'value' => $customFieldHTML,
				'type' => 'custom'
			);
		}
		else
		{
			$arResult['FIELDS']['tab_params'][] = array(
				'id' => 'PROPERTY_DEFAULT_VALUE',
				'name' => GetMessage('CRM_PRODUCT_PE_PROP_DEFAULT'),
				'value' => htmlspecialcharsbx($arProperty['DEFAULT_VALUE']),
				'type' => 'text',
				'params' => array('size' => '50', 'maxlength' => '2000')
			);
		}
	}
}

if ($userTypeSettingsHTML)
{
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'USER_TYPE_SETTINGS_SECTION',
		'name' => (isset($arPropertyFields["USER_TYPE_SETTINGS_TITLE"]) && '' != trim($arPropertyFields["USER_TYPE_SETTINGS_TITLE"]) ? $arPropertyFields["USER_TYPE_SETTINGS_TITLE"] : GetMessage("CRM_PRODUCT_PE_PROP_USER_TYPE_SETTINGS")),
		'type' => 'section'
	);

	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'USER_TYPE_SETTINGS',
		'value' => $userTypeSettingsHTML,
		'type' => 'custom',
		'colspan' => true
	);
}



$this->IncludeComponentTemplate();
?>