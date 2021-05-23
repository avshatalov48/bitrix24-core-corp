<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CAllCrmInvoice::installExternalEntities())
	return;


if (!CModule::IncludeModule('iblock'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_IBLOCK'));
	return;
}
if (!CModule::IncludeModule('currency'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY'));
	return;
}
if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));
	return;
}
if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));
	return;
}

global $APPLICATION, $USER;
$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['PATH_TO_EXCH1C_INDEX'] = CrmCheckPath('PATH_TO_EXCH1C_INDEX', $arParams['PATH_TO_EXCH1C_INDEX'], $APPLICATION->GetCurPage());
$arResult['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arResult['BACK_URL'] = $arParams['PATH_TO_EXCH1C_INDEX'];
$arResult['FORM_ID'] = 'CRM_EXCH1C_CONFIG';
$arResult['FIELDS'] = array();



// <editor-fold defaultstate="collapsed" desc="Invoice export options">
// --> Invoice export options
$arUGroupsEx = Array();
$arAction = array(
	"N" => GetMessage("CAT_1C_NONE"),
	"A" => GetMessage("CAT_1C_DEACTIVATE"),
	"D" => GetMessage("CAT_1C_DELETE"),
);
$iblockType = CCrmCatalog::GetCatalogTypeID();

$arStatuses = Array("" => GetMessage("SALE_1C_NO"));
foreach(CCrmStatus::GetStatusList('INVOICE_STATUS') as $k => $v)
	$arStatuses[$k] = '['.$k.'] '.$v;

$arAllOptions = array(
	array("1C_SALE_SITE_LIST", GetMessage("SALE_1C_SITE_LIST"), SITE_ID, Array("text", 2), "hidden", 1),
	array("1C_SALE_ACCOUNT_NUMBER_SHOP_PREFIX", GetMessage("SALE_1C_SALE_ACCOUNT_NUMBER_SHOP_PREFIX"), "CRM_", Array("text"), "visible", 1),
	array("1C_EXPORT_PAYED_ORDERS", GetMessage("SALE_1C_EXPORT_PAYED_ORDERS"), "", Array("checkbox"), "hidden", 1),
	array("1C_EXPORT_ALLOW_DELIVERY_ORDERS", GetMessage("SALE_1C_EXPORT_ALLOW_DELIVERY_ORDERS"), "", Array("checkbox"), "hidden", 1),
	array("1C_EXPORT_FINAL_ORDERS", GetMessage("SALE_1C_EXPORT_FINAL_ORDERS"), "", Array("list", $arStatuses), "visible", 1),
	array("1C_FINAL_STATUS_ON_DELIVERY", GetMessage("SALE_1C_FINAL_STATUS_ON_DELIVERY"), "", Array("list", $arStatuses), "visible", 1),
	array("1C_REPLACE_CURRENCY", GetMessage("SALE_1C_REPLACE_CURRENCY"), GetMessage("SALE_1C_RUB"), Array("text"), "visible", 1),
	array("1C_SALE_GROUP_PERMISSIONS", GetMessage("SALE_1C_GROUP_PERMISSIONS"), "-", Array("mlist", 5, $arUGroupsEx), "hidden", 1),
	array("1C_SALE_USE_ZIP", GetMessage("SALE_1C_USE_ZIP"), "Y", Array("checkbox"), "hidden", 1),
);

$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();

$tabNames = array(
	1 => 'tab_invoice_export',
	2 => 'tab_invoice_prof_com',
	3 => 'tab_invoice_prof_con'
);

$personTypeTabNumbers = array(
	$arPersonTypes['COMPANY'] => 2,
	$arPersonTypes['CONTACT'] => 3
);

$arAgent = Array(
	"CONTACT" => Array(
		"SURNAME" => GetMessage("SOG_SURNAME"),
		"NAME" => GetMessage("SOG_NAME"),
		"SECOND_NAME" => GetMessage("SOG_SECOND_NAME"),
		"BIRTHDAY" => GetMessage("SOG_BIRTHDAY"),
		"MALE" => GetMessage("SOG_MALE"),
		"INN" => GetMessage("SOG_INN"),
		"KPP" => GetMessage("SOG_KPP"),
		"ADDRESS_FULL" => GetMessage("SOG_ADDRESS_FULL"),
		"INDEX" => GetMessage("SOG_INDEX"),
		"COUNTRY" => GetMessage("SOG_COUNTRY"),
		"REGION" => GetMessage("SOG_REGION"),
		"STATE" => GetMessage("SOG_STATE"),
		"TOWN" => GetMessage("SOG_TOWN"),
		"CITY" => GetMessage("SOG_CITY"),
		"STREET" => GetMessage("SOG_STREET"),
		"BUILDING" => GetMessage("SOG_BUILDING"),
		"HOUSE" => GetMessage("SOG_HOUSE"),
		"FLAT" => GetMessage("SOG_FLAT"),
	),
	"COMPANY" => Array(
		"ADDRESS_FULL" => GetMessage("SOG_ADDRESS_FULL"),
		"INDEX" => GetMessage("SOG_INDEX"),
		"COUNTRY" => GetMessage("SOG_COUNTRY"),
		"REGION" => GetMessage("SOG_REGION"),
		"STATE" => GetMessage("SOG_STATE"),
		"TOWN" => GetMessage("SOG_TOWN"),
		"CITY" => GetMessage("SOG_CITY"),
		"STREET" => GetMessage("SOG_STREET"),
		"BUILDING" => GetMessage("SOG_BUILDING"),
		"HOUSE" => GetMessage("SOG_HOUSE"),
		"FLAT" => GetMessage("SOG_FLAT"),
		"INN" => GetMessage("SOG_INN"),
		"KPP" => GetMessage("SOG_KPP"),
		"EGRPO" => GetMessage("SOG_EGRPO"),
		"OKVED" => GetMessage("SOG_OKVED"),
		"OKDP" => GetMessage("SOG_OKDP"),
		"OKOPF" => GetMessage("SOG_OKOPF"),
		"OKFC" => GetMessage("SOG_OKFC"),
		"OKPO" => GetMessage("SOG_OKPO"),
		"ACCOUNT_NUMBER" => GetMessage("SOG_ACCOUNT_NUMBER"),
		"B_NAME" => GetMessage("SOG_B_NAME"),
		"B_BIK" => GetMessage("SOG_B_BIK"),
		"B_ADDRESS_FULL" => GetMessage("SOG_B_ADDRESS_FULL"),
		"B_INDEX" => GetMessage("SOG_B_INDEX"),
		"B_COUNTRY" => GetMessage("SOG_B_COUNTRY"),
		"B_REGION" => GetMessage("SOG_B_REGION"),
		"B_STATE" => GetMessage("SOG_B_STATE"),
		"B_TOWN" => GetMessage("SOG_B_TOWN"),
		"B_CITY" => GetMessage("SOG_B_CITY"),
		"B_STREET" => GetMessage("SOG_B_STREET"),
		"B_BUILDING" => GetMessage("SOG_B_BUILDING"),
		"B_HOUSE" => GetMessage("SOG_B_HOUSE"),
	),
);
$arAgentInfo = array();
foreach (array_keys($arPersonTypes) as $type)
{
	if ($type === 'COMPANY' || $type === 'CONTACT')
	{
		$agentInfo = Array(
			"AGENT_NAME" =>  GetMessage("SOG_AGENT_NAME"),
			"FULL_NAME" => GetMessage("SOG_FULL_NAME"),
		);

		foreach($arAgent[$type] as $k => $v)
			$agentInfo[$k] = $v;

		$agentInfo["PHONE"] = GetMessage("SOG_PHONE");
		$agentInfo["EMAIL"] = GetMessage("SOG_EMAIL");
		$agentInfo["CONTACT_PERSON"] = GetMessage("SOG_CONTACT_PERSON");
		$agentInfo["F_ADDRESS_FULL"] = GetMessage("SOG_F_ADDRESS_FULL");
		$agentInfo["F_INDEX"] = GetMessage("SOG_F_INDEX");
		$agentInfo["F_COUNTRY"] = GetMessage("SOG_F_COUNTRY");
		$agentInfo["F_REGION"] = GetMessage("SOG_F_REGION");
		$agentInfo["F_STATE"] = GetMessage("SOG_F_STATE");
		$agentInfo["F_TOWN"] = GetMessage("SOG_F_TOWN");
		$agentInfo["F_CITY"] = GetMessage("SOG_F_CITY");
		$agentInfo["F_STREET"] = GetMessage("SOG_F_STREET");
		$agentInfo["F_BUILDING"] = GetMessage("SOG_F_BUILDING");
		$agentInfo["F_HOUSE"] = GetMessage("SOG_F_HOUSE");
		$agentInfo["F_FLAT"] = GetMessage("SOG_F_FLAT");

		$arAgentInfo[$type] = $agentInfo;
	}
}

if($_SERVER['REQUEST_METHOD'] == "POST" && $_POST['save'] != "" && check_bitrix_sessid())
{
	for ($i = 0, $intCount = count($arAllOptions); $i < $intCount; $i++)
	{
		$tabNumber = $arAllOptions[$i][5];
		$name = $arAllOptions[$i][0];
		if (isset($arAllOptions[$i][4]) && $arAllOptions[$i][4] === 'hidden')
		{
			$val = $arAllOptions[$i][2];
		}
		else
		{
			$val = $_REQUEST[$tabNumber.'_'.$name];
		}
		if($arAllOptions[$i][3][0] === 'checkbox' && $val != 'Y')
		{
			$val = "N";
		}
		if($arAllOptions[$i][3][0] === 'mlist')
		{
			$val = is_array($val) ? implode(',', $val) : '';
		}
		COption::SetOptionString("sale", $name, $val, $arAllOptions[$i][1]);
	}

	$personTypes = \Bitrix\Sale\BusinessValue::getPersonTypes();
	foreach ($personTypes as $personTypeId => $personType)
	{
		ExportOneCCRM::deleteREKV($personType['ID']);
		ExportOneCCRM::Delete($personType['ID']);
	}

	// <editor-fold defaultstate="collapsed" desc="invoice export profiles">
	// invoice export profiles
	$dbExport = ExportOneCCRM::GetList();
	$arExportProfile = array();
	while($arExport = $dbExport->Fetch())
	{
		$arExportProfile[$arExport["PERSON_TYPE_ID"]] = $arExport["ID"];
	}

	foreach ($arPersonTypes as $personType => $personTypeId)
	{
		$arParams = array();
		$tabNumber = $personTypeTabNumbers[$personTypeId];

		if (count($arAgentInfo[$personType]) > 0)
		{
			$arActFields = array_keys($arAgentInfo[$personType]);
			$actFieldsCount = count($arActFields);
			for ($i = 0; $i < $actFieldsCount; $i++)
			{
				$arActFields[$i] = trim($arActFields[$i]);

				$typeTmp = $_POST["TYPE_".$arActFields[$i]."_".$tabNumber];
				$pref = ($typeTmp == '') ? "VALUE1_" : (($typeTmp === "ORDER") ? "VALUE2_" : (($typeTmp === "PROPERTY") ? "VALUE3_" : ""));
				if ($pref != "")
				{
					$valueTmp = $_POST[$pref.$arActFields[$i]."_".$tabNumber];

					$arParams[$arActFields[$i]] = array(
						"TYPE" => $typeTmp,
						"VALUE" => $valueTmp
					);
				}
			}
			$arParams["IS_FIZ"] = ($personType === "CONTACT") ? "Y" : "N";

			$i = 0;
			foreach($_POST as $k => $v)
			{
				if(mb_strpos($k, "REKV_".$tabNumber) !== false && $v <> '')
				{
					$ind = mb_substr($k, mb_strrpos($k, "_") + 1);

					$typeTmp = $_POST["TYPE_REKV_".$ind."_".$tabNumber];
					$pref = ($typeTmp == '') ? "VALUE1_REKV_" : (($typeTmp === "ORDER") ? "VALUE2_REKV_" : (($typeTmp === "PROPERTY") ? "VALUE3_REKV_" : ""));
					if ($pref != "")
					{
						$valueTmp = $_POST[$pref.$ind."_".$tabNumber];

						if($v <> '' && $valueTmp <> '')
						{
							$arParams["REKV_".$i] = array(
								"TYPE" => $typeTmp,
								"VALUE" => $valueTmp,
								"NAME" => $v,
							);
							$i++;
						}
					}
				}
			}
		}
		if(intval($arExportProfile[$personTypeId])>0)
			$res = ExportOneCCRM::Update($arExportProfile[$personTypeId], Array("PERSON_TYPE_ID" => $personTypeId, "VARS" => serialize($arParams)));
		else
			$res = ExportOneCCRM::Add(Array("PERSON_TYPE_ID" => $personTypeId, "VARS" => serialize($arParams)));
	}
	// </editor-fold>

	$tmp = CComponentEngine::MakePathFromTemplate($arResult['PATH_TO_EXCH1C_INDEX'], array());
	if (!(isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y"))
	{
		LocalRedirect(CComponentEngine::MakePathFromTemplate($arResult['PATH_TO_EXCH1C_INDEX'], array()));
	}
}

foreach($arAllOptions as $Option)
{
	$tabNumber = $Option[5];
	if (isset($Option[4]) && $Option[4] === 'hidden')
		continue;

	$val = COption::GetOptionString("sale", $Option[0], $Option[2]);
	$type = $Option[3];

	$fieldParams = array(
		'id' => $tabNumber.'_'.$Option[0],
		'name' => $Option[1],
		'type' => ($type[0] === 'mlist') ? 'list' : $type[0],
		'value' => ($type[0] === 'mlist') ? explode(",", $val) : $val/*,
		'required' => true*/
	);
	if ($type[0] === 'list' || $type[0] === 'mlist')
		$fieldParams['items'] = $type[1];
	if ($type[0] === "text" || $type[0] === "date" || $type[0] === "file")
	{
		if (isset($type[1]))
			$fieldParams['params'] = array("size" => $type[1]);
	}

	$arResult['FIELDS'][$tabNames[$tabNumber]][] = $fieldParams;
}
// <-- Invoice export options
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Invoice profile options">
// --> Invoice profile options

// get profile settings
$expParams = array();
$expRekvParams = array();
$dbExport = ExportOneCCRM::GetList();
while($arExport = $dbExport->Fetch())
{
	$personType1C = "UR";
	$arExpParams = unserialize($arExport["VARS"], ['allowed_classes' => false]);
	foreach($arExpParams as $k => $v)
	{
		if ($k === "IS_FIZ")
		{
			$personType1C = ($v === "Y") ? "FIZ" : "UR";
			continue;
		}
		if($v["NAME"] <> '')
		{
			$k = str_replace("REKV_", "REKV_n", $k);
			$expParams[$arExport["PERSON_TYPE_ID"]][$k]["NAME"] = $v["NAME"];
		}
		if(mb_strpos($k, "REKV_") !== false)
		{
			$expRekvParams[$personTypeTabNumbers[$arExport["PERSON_TYPE_ID"]]][$k]["NAME"] = $v["NAME"];
			$expRekvParams[$personTypeTabNumbers[$arExport["PERSON_TYPE_ID"]]][$k]["TYPE"] = $v["TYPE"];
			$expRekvParams[$personTypeTabNumbers[$arExport["PERSON_TYPE_ID"]]][$k]["VALUE"] = $v["VALUE"];
		}
		else
		{
			$expParams[$arExport["PERSON_TYPE_ID"]][$k]["TYPE"] = $v["TYPE"];
			$expParams[$arExport["PERSON_TYPE_ID"]][$k]["VALUE"] = $v["VALUE"];
		}
	}
}

// invoice fields
$arOrderFieldsList = array('ID', 'DATE_INSERT', 'DATE_INSERT_DATE', 'SHOULD_PAY', 'CURRENCY', 'PRICE', 'LID', 'PRICE_DELIVERY', 'DISCOUNT_VALUE', 'USER_ID', 'PAYSYSTEM_ID', 'DELIVERY_ID', 'TAX_VALUE');
$arOrderFieldsNameList = array(GetMessage('SPS_ORDER_ID'), GetMessage('SPS_ORDER_DATETIME'), GetMessage('SPS_ORDER_DATE'), GetMessage('SPS_ORDER_PRICE'), GetMessage('SPS_ORDER_CURRENCY'), GetMessage('SPS_ORDER_SUM'), GetMessage('SPS_ORDER_SITE'), GetMessage('SPS_ORDER_PRICE_DELIV'), GetMessage('SPS_ORDER_DESCOUNT'), GetMessage('SPS_ORDER_USER_ID'), GetMessage('SPS_ORDER_PS'), GetMessage('SPS_ORDER_DELIV'), GetMessage('SPS_ORDER_TAX'));

// invoice properties fields
$arPropFieldsList = array();
$arPropFieldsNameList = array();

$dbRes = \Bitrix\Crm\Invoice\Property::getList([
	'select' => [
		'ID', 'PERSON_TYPE_ID', 'CODE', 'NAME', 'TYPE', 'SORT'
	],
	'order' => [
		'SORT' => 'ASC',
		'NAME' => 'ASC'
	]
]);

$arPropertiesInfo = array();
while ($arOrderProp = $dbRes->fetch())
{
	if (isset($arOrderProp['PERSON_TYPE_ID']))
	{
		$arPropertiesInfo[$arOrderProp['PERSON_TYPE_ID']][] = $arOrderProp;
	}
}
foreach ($arPersonTypes as $personTypeId)
{
	$arPropFieldsList[$personTypeId] = array();
	$arPropFieldsNameList[$personTypeId] = array();
	$i = -1;
	foreach ($arPropertiesInfo[$personTypeId] as $invoiceProperty)
	{
		$i++;
		$arPropFieldsList[$personTypeId][$i] = $invoiceProperty['ID'];
		$arPropFieldsNameList[$personTypeId][$i] = $invoiceProperty['NAME'];
		if ($invoiceProperty["TYPE"] == "LOCATION")
		{
			$i++;
			$arPropFieldsList[$personTypeId][$i] = $invoiceProperty['ID']."_COUNTRY";
			$arPropFieldsNameList[$personTypeId][$i] = $invoiceProperty['NAME']." (".GetMessage("SPS_JCOUNTRY").")";
			
			$i++;
			$arPropFieldsList[$personTypeId][$i] = $invoiceProperty['ID']."_CITY";
			$arPropFieldsNameList[$personTypeId][$i] = $invoiceProperty['NAME']." (".GetMessage("SPS_JCITY").")";
		}
	}
}

$arLastFieldInfo = array();
$arEmptyFields = array();
foreach (array_keys($arAgentInfo) as $type)
{
	$fields = array();
	$personTypeId = $arPersonTypes[$type];
	$tabNumber = $personTypeTabNumbers[$personTypeId];
	$lastId = false;
	foreach ($arAgentInfo[$type] as $id => $name)
	{
		$fieldType = $expParams[$personTypeId][$id]['TYPE'];
		$fieldValue = $expParams[$personTypeId][$id]['VALUE'];
		$fieldHtml =
			'<select name="TYPE_'.$id.'_'.$tabNumber.'" id="TYPE_'.$id.'_'.
			$tabNumber.'" OnChange="BX.crmExch1cInvMan.PropertyTypeChange(\''.$id.'\', '.$tabNumber.')">'.
			'<option value=""'.(($fieldType !== 'ORDER' && $fieldType !== 'PROPERTY') ? ' selected="selected"' : '').'>'.GetMessage("SPSG_OTHER").'</option>'.PHP_EOL.
			'<option value="ORDER"'.(($fieldType === 'ORDER') ? ' selected="selected"' : '').'>'.GetMessage("SPSG_FROM_ORDER").'</option>'.PHP_EOL.
			'<option value="PROPERTY"'.(($fieldType === 'PROPERTY') ? ' selected="selected"' : '').'>'.GetMessage("SPSG_FROM_PROPS").'</option>'.PHP_EOL.
			'</select>'.
			'<select id="'.'VALUE2_'.$id.'_'.$tabNumber.'" class="crm-exch1c-val-ctrl" name="'.'VALUE2_'.$id.'_'.$tabNumber.'"'.(($fieldType !== 'ORDER') ? ' style="display: none;"' : '').'>'.PHP_EOL;
		$nCount = count($arOrderFieldsList);
		for ($i = 0; $i < $nCount; $i++)
			$fieldHtml .= '<option value="'.$arOrderFieldsList[$i].'"'.(($fieldType === 'ORDER' && $arOrderFieldsList[$i] == $fieldValue) ? ' selected="selected"' : '').'>'.htmlspecialcharsbx($arOrderFieldsNameList[$i]).'</option>'.PHP_EOL;
		unset($nCount);
		$fieldHtml .=
			'</select>'.
			'<select id="'.'VALUE3_'.$id.'_'.$tabNumber.'" class="crm-exch1c-val-ctrl" name="'.'VALUE3_'.$id.'_'.$tabNumber.'"'.(($fieldType !== 'PROPERTY') ? ' style="display: none;"' : '').'>'.PHP_EOL;
		$nCount = count($arPropFieldsList[$personTypeId]);
		for ($i = 0; $i < $nCount; $i++)
			$fieldHtml .= '<option value="'.htmlspecialcharsbx($arPropFieldsList[$personTypeId][$i]).'"'.(($fieldType === 'PROPERTY' && $arPropFieldsList[$personTypeId][$i] == $fieldValue) ? ' selected="selected"' : '').'>'.htmlspecialcharsbx($arPropFieldsNameList[$personTypeId][$i]).'</option>'.PHP_EOL;
		unset($nCount);
		$fieldHtml .= '</select>'.
			'<input id="'.'VALUE1_'.$id.'_'.$tabNumber.'" class="crm-exch1c-val-ctrl" type="text" name="'.'VALUE1_'.$id.'_'.$tabNumber.'"'.(($fieldType !== 'ORDER' && $fieldType !== 'PROPERTY') ? ' value="'.htmlspecialcharsbx($fieldValue).'"' : ' value="" style="display: none;"').' maxlength="180" />';

		$lastId = 'TYPE_'.$id.'_'.$tabNumber;
		$fields[] = array(
			'id' => $lastId,
			'name' => $name,
			'type' => 'custom',
			'value' => $fieldHtml
		);
		if (empty($fieldValue))
			$arEmptyFields[$tabNumber][] = $lastId;
	}
	if ($lastId)
		$arLastFieldInfo[$tabNumber] = $lastId;

	$tabName = ($type === 'COMPANY') ? 'tab_invoice_prof_com' : (($type === 'CONTACT') ? 'tab_invoice_prof_con' : 0);
	if (!empty($tabName) && count($fields) > 0)
		$arResult['FIELDS'][$tabName] = $fields;
}

$arResult['EXCH1C_MAN_SETTINGS'] = array(
	'addRekvButtonTitle' => GetMessage('SPSG_ADD'),
	'showEmptyFieldsButtonTitle' => GetMessage('CRM_EXCH1C_BTN_SHOW_ALL_TITLE'),
	'rekvTitle' => GetMessage('CRM_EXCH1C_REKV_TITLE'),
	'expRekvParams' => $expRekvParams,
	'arLastFieldInfo' => $arLastFieldInfo,
	'arEmptyFields' => $arEmptyFields,
	'arOrderFieldsList' => $arOrderFieldsList,
	'arOrderFieldsNameList' => $arOrderFieldsNameList,
	'arPropFieldsList' => $arPropFieldsList,
	'arPropFieldsNameList' => $arPropFieldsNameList,
	'fieldTypes' => array(
		'other' => GetMessage('SPSG_OTHER'),
		'order' => GetMessage('SPSG_FROM_ORDER'),
		'property' => GetMessage('SPSG_FROM_PROPS'),
	),
	'tabNumberPersonType' => array(
		$personTypeTabNumbers[$arPersonTypes['COMPANY']] => $arPersonTypes['COMPANY'],
		$personTypeTabNumbers[$arPersonTypes['CONTACT']] => $arPersonTypes['CONTACT'],
	),
	'accountNumberWarningTitle' => GetMessage('CRM_ACCOUNT_NUMBER_WARNING_TITLE'),
	'tabInvoiceExportId' => $tabNames[1].'_edit_table',
	'accountNumberInputName' => '1_1C_SALE_ACCOUNT_NUMBER_SHOP_PREFIX'
);
// <-- Invoice profile options
// </editor-fold>

$this->IncludeComponentTemplate();

$APPLICATION->AddChainItem(GetMessage('CRM_EXCH1C_LIST'), $arParams['PATH_TO_EXCH1C_INDEX']);

?>
