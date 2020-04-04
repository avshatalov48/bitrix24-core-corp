<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
// *****************************************************************************************
// input params
// $bVarsFromForm -
// arUserField USER_TYPE - type UF
// arUserField VALUE- value UF
// *****************************************************************************************
$arParams["bVarsFromForm"] = $arParams["bVarsFromForm"] ? true:false;
$arResult["VALUE"] = false;
$arUserField = &$arParams["arUserField"];
$arFilter = $arParams["arFilter"];

// *****************************************************************************************

if(isset($arUserField['USER_TYPE']))
{
	if(!$arParams["bVarsFromForm"])
	{
		if(
			$arUserField["ENTITY_VALUE_ID"] <= 0
			&& !is_array($arUserField["SETTINGS"]["DEFAULT_VALUE"])
			&& strlen($arUserField["SETTINGS"]["DEFAULT_VALUE"]) > 0
		)
			$arResult["VALUE"] = '';//$arParams["~arUserField"]["SETTINGS"]["DEFAULT_VALUE"];
		else
			$arResult["VALUE"] = $arParams["~arUserField"]["VALUE"];
	}
	elseif(is_array($arUserField['USER_TYPE']))
	{
		if ($arUserField["USER_TYPE"]["BASE_TYPE"]=="file")
			$arResult["VALUE"] = $GLOBALS[$arUserField["FIELD_NAME"]."_old_id"];
		else if ($arUserField["USER_TYPE"]["USER_TYPE_ID"] == 'integer'
			|| $arUserField["USER_TYPE"]["USER_TYPE_ID"] == 'double'
			|| $arUserField["USER_TYPE"]["USER_TYPE_ID"] == 'datetime'
			|| $arUserField["USER_TYPE"]["USER_TYPE_ID"] == 'date')
		{
			$arResult["VALUE"] = array();
			$val1 = '';
			$val2 = '';
			$key1 = $arUserField["FIELD_NAME"].'_from';
			$key2 = $arUserField["FIELD_NAME"].'_to';
			if(isset($_REQUEST[$key1]))
			{
				$val1 = $_REQUEST[$key1];
			}
			elseif(isset($arFilter[$key1]))
			{
				$val1 = $arFilter[$key1];
			}

			if (isset($_REQUEST[$key2]))
			{
				$val2 = $_REQUEST[$key2];
			}
			elseif(isset($arFilter[$key2]))
			{
				$val2 = $arFilter[$key2];
			}

			$arResult["VALUE"][] = array(0 => $val1, 1 => $val2);
		}
		elseif(isset($_REQUEST[$arUserField["FIELD_NAME"]]))
		{
			$arResult["VALUE"] = $_REQUEST[$arUserField["FIELD_NAME"]];
		}
		elseif(isset($arFilter[$arUserField["FIELD_NAME"]]))
		{
			$arResult["VALUE"] = $arFilter[$arUserField["FIELD_NAME"]];
		}
	}

	if (!is_array($arResult["VALUE"]))
		$arResult["VALUE"] = array($arResult["VALUE"]);
	if (empty($arResult["VALUE"]))
		$arResult["VALUE"] = array(null);

	if(is_array($arUserField['USER_TYPE']))
	{
		foreach ($arResult["VALUE"] as $key => $res)
		{
			switch ($arUserField["USER_TYPE"]["BASE_TYPE"])
			{
				case "double":
					if (isset($res[0]) && strlen($res[0])>0)
						$res[0] = round(doubleval($res[0]), $arUserField["SETTINGS"]["PRECISION"]);
					if (isset($res[1]) && strlen($res[1])>0)
						$res[1] = round(doubleval($res[1]), $arUserField["SETTINGS"]["PRECISION"]);
					$arResult["VALUE"][$key] = $res;
					break;
				case "int":
					if ($arUserField["USER_TYPE"]["USER_TYPE_ID"] == "integer")
					{
						$res[0] = strlen($res[0])>0 ? (int) $res[0] : '';
						$res[1] = strlen($res[1])>0 ? (int) $res[1] : '';
						$arResult["VALUE"][$key] = $res;
					}
					else
						$arResult["VALUE"][$key] = strlen($res)>0 ? (int) $res : '';
					break;
				case "datetime":
					break;
				case "date":
					break;
				case "boolean":
					break;
				default:
					$arResult["VALUE"][$key] = htmlspecialcharsbx($res);
					break;
			}
		}
	}

	$arUserField["~FIELD_NAME"] = $arUserField["FIELD_NAME"];
/*	if ($arUserField["MULTIPLE"]=="Y")
	{
		$arUserField["~FIELD_NAME"] = $arUserField["FIELD_NAME"];
		$arUserField["FIELD_NAME"] .= "[]";

		if (!empty($arResult["VALUE"]) && (!empty($arResult["VALUE"][count($arResult["VALUE"])-1])))
		{
			//$arResult["VALUE"][] = null;
		}
	}*/

	if (is_array($arUserField['USER_TYPE']) && is_callable(array($arUserField["USER_TYPE"]['CLASS_NAME'], 'getlist')))
	{
		$enum = array();

		if(
			($arUserField["MANDATORY"] != "Y")
			&& ($arUserField["SETTINGS"]["DISPLAY"] != "CHECKBOX")
		):
			$enum = array(/*null=>GetMessage("MAIN_NO")*/);
		endif;

		$rsEnum = call_user_func_array(
			array($arParams['arUserField']["USER_TYPE"]["CLASS_NAME"], "getlist"),
			array(
				$arParams['arUserField'],
			)
		);

		if(!$arParams["bVarsFromForm"] && ($arUserField["ENTITY_VALUE_ID"] <= 0))
			$arResult["VALUE"] = array();

		while($arEnum = $rsEnum->GetNext())
		{
			$enum[$arEnum["ID"]] = $arEnum["VALUE"];
			if(!$arParams["bVarsFromForm"] && ($arUserField["ENTITY_VALUE_ID"] <= 0))
			{
				if($arEnum["DEF"] == "Y")
					$arResult["VALUE"][] = $arEnum["ID"];
			}
		}
		$arUserField["USER_TYPE"]["FIELDS"] = $enum;
	}

	$arParams["form_name"] = !empty($arParams["form_name"]) ? $arParams["form_name"] : "form1";
	if(!$this->initComponentTemplate())
	{
		$this->SetTemplateName("string");
	}
	$this->IncludeComponentTemplate();

}?>