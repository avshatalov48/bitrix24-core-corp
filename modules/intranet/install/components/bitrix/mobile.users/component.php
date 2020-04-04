<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!CModule::IncludeModule("intranet"))
{
    ShowError(GetMessage("EM_MODULE_IS_NOT_INSTALLED")); 
    return 0;
}

if(IntVal($arParams["dep"]) > 0)
	$arParams["dep"] =  IntVal($arParams["dep"]);
if(IntVal($arParams["id"]) > 0)
	$arParams["id"] =  IntVal($arParams["id"]);
	
if(strlen($_REQUEST["name"]) > 0)
	$arResult["search_name"] = $_REQUEST["name"];
	
$arParams['PATH_TO_COMPANY_DEPARTMENT'] = "index.php?dep=#ID#";
$arParams['PATH_TO_USER'] = "index.php?id=#ID#";
$arParams['PATH_TO_CHAT'] = SITE_DIR."m/messages/chat.php?id=#ID#";

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 3600;
	
if ($arParams['CACHE_TYPE'] == 'A')
	$arParams['CACHE_TYPE'] = COption::GetOptionString("main", "component_cache_on", "Y");


$componentPage = "index";

$arListParams = array('SELECT' => array('UF_*'), "NAV_PARAMS" => Array("nPageSize"=>10));
$arFilter = array("ACTIVE"=>"Y");
$arFilter["!UF_DEPARTMENT"] = false;
if(IntVal($_REQUEST["dep"]) > 0)
{
	$arFilter["UF_DEPARTMENT"] = $_REQUEST["dep"];
	$componentPage = "department";
	$arResult["cur_dep"] = IntVal($_REQUEST["dep"]);
}
if(IntVal($_REQUEST["id"]) > 0)
{
	$arFilter["ID"] = $_REQUEST["id"];
	$componentPage = "user";
	unset($arListParams["NAV_PARAMS"]);
}
if(strlen($arResult["search_name"]) > 0)
{
	$arFilter["NAME"] = $arResult["search_name"];
}


CpageOption::SetOptionString("main", "nav_page_in_session", "N");

$arSelect = array('ID', 'TIMESTAMP_X', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'EMAIL', 'LID', 'DATE_REGISTER',  'PERSONAL_PROFESSION', 'PERSONAL_WWW', 'PERSONAL_ICQ', 'PERSONAL_GENDER', 'PERSONAL_BIRTHDATE', 'PERSONAL_PHOTO', 'PERSONAL_PHONE', 'PERSONAL_FAX', 'PERSONAL_MOBILE', 'PERSONAL_PAGER', 'PERSONAL_STREET', 'PERSONAL_MAILBOX', 'PERSONAL_CITY', 'PERSONAL_STATE', 'PERSONAL_ZIP', 'PERSONAL_COUNTRY', 'PERSONAL_NOTES', 'WORK_COMPANY', 'WORK_DEPARTMENT', 'WORK_POSITION', 'WORK_WWW', 'WORK_PHONE', 'WORK_FAX', 'WORK_PAGER', 'WORK_STREET', 'WORK_MAILBOX', 'WORK_CITY', 'WORK_STATE', 'WORK_ZIP', 'WORK_COUNTRY', 'WORK_PROFILE', 'WORK_LOGO', 'WORK_NOTES', 'PERSONAL_BIRTHDAY', 'LAST_ACTIVITY_DATE', 'IS_ONLINE');

$dbUsers = CUser::GetList(
	($sort_by = 'last_name'), ($sort_order = 'asc'), 
	$arFilter, 
	$arListParams
);
$arResult["NAV_STRING"] = $dbUsers->GetPageNavString("", $arParams["NAV_TEMPLATE"]);

$arUsers = array();
while($arUser = $dbUsers->Fetch())
{
	foreach($arUser as $k => $value)
	{
		if(!in_array($k, $arSelect) && substr($k, 0, 3) != 'UF_') 
			unset($arUser[$k]);
		elseif ($k == "PERSONAL_COUNTRY" || $k == "WORK_COUNTRY")
			$arUser[$k] = GetCountryByID($value);
	}

	$arUser['URL'] = str_replace('#ID#', $arUser['ID'], $arParams['PATH_TO_USER']);
	$arUser['CHAT_URL'] = str_replace('#ID#', $arUser['ID'], $arParams['PATH_TO_CHAT']);
	if($arUser['PERSONAL_PHOTO']>0)
	{
		$arUser['PERSONAL_PHOTO_B'] = CFile::ResizeImageGet($arUser['PERSONAL_PHOTO'], array("width"=>80, "height"=>80));
		$arUser['PERSONAL_PHOTO_S'] = CFile::ResizeImageGet($arUser['PERSONAL_PHOTO'], array("width"=>40, "height"=>40));
	}
	$arUser["IS_ONLINE"] = ($arUser["IS_ONLINE"] == "Y");

	if($componentPage == "user")
	{

		if (CModule::IncludeModule('intranet'))
		{
			$arResult['IS_HONOURED'] = CIntranetUtils::IsUserHonoured($arUser["ID"]);
			//$arResult['IS_ABSENT'] = CIntranetUtils::IsUserAbsent($arUser["ID"], $arParams['CALENDAR_USER_IBLOCK_ID']);

			//departments and managers
			$obCache = new CPHPCache; 
			$path = "/user_card_".intval($arUser["ID"] / TAGGED_user_card_size);
			if($arParams["CACHE_TIME"] == 0 || $obCache->StartDataCache($arParams["CACHE_TIME"], $arUser["ID"], $path))
			{
				if($arParams["CACHE_TIME"] > 0 && defined("BX_COMP_MANAGED_CACHE"))
				{
					$GLOBALS["CACHE_MANAGER"]->StartTagCache($path);
					$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_CARD_".intval($arUser["ID"] / TAGGED_user_card_size));
				}

				//departments
				$arResult['DEPARTMENTS'] = array();
				$dbRes = CIntranetUtils::GetSubordinateDepartmentsList($arUser["ID"]);
				while ($arRes = $dbRes->GetNext())
				{
					$arRes['URL'] = str_replace('#ID#', $arRes['ID'], $arParams['PATH_TO_COMPANY_DEPARTMENT']);
					$arResult['DEPARTMENTS'][$arRes['ID']] = $arRes;
					$arResult['DEPARTMENTS'][$arRes['ID']]['EMPLOYEE_COUNT'] = 0;
		
					$rsUsers1 = CIntranetUtils::getDepartmentEmployees(array($arRes['ID']), true, false, 'Y', array('ID'));
					while($arUser1 = $rsUsers1->Fetch())
					{
						if($arUser1['ID'] <> $arUser["ID"]) //self
							$arResult['DEPARTMENTS'][$arRes['ID']]['EMPLOYEE_COUNT'] ++;
					}
				}
				
				//managers
				$arRes = CIntranetUtils::GetDepartmentManager($arUser["UF_DEPARTMENT"], $arUser["ID"], true);				
				foreach($arRes as $val)
				{
					$arResult["MANAGERS"][$val["ID"]]["ID"] = $val["ID"];
					$arResult["MANAGERS"][$val["ID"]]["NAME"] = $val["LAST_NAME"]." ".$val["NAME"];
					$arResult["MANAGERS"][$val["ID"]]["URL"] = str_replace('#ID#', $val['ID'], $arParams['PATH_TO_USER']);
					$arResult["MANAGERS"][$val["ID"]]["PHOTO"] = CFile::ResizeImageGet($val['PERSONAL_PHOTO'], array("width"=>30, "height"=>30));
				}

				if($arParams["CACHE_TIME"] > 0)
				{
					$obCache->EndDataCache(array(
						'DEPARTMENTS' => $arResult['DEPARTMENTS'],
						'MANAGERS' => $arResult['MANAGERS'],
					)); 
					if(defined("BX_COMP_MANAGED_CACHE"))
						$GLOBALS["CACHE_MANAGER"]->EndTagCache();
				}
			}
			elseif($arParams["CACHE_TIME"] > 0)
			{
				$vars = $obCache->GetVars();
				$arResult['DEPARTMENTS'] = $vars['DEPARTMENTS'];
				$arResult['MANAGERS'] = $vars['MANAGERS'];
			}
		}
	}

	$arUsers[$arUser["ID"]] = $arUser;
}

$obCache = new CPHPCache; 
$cache_id = "mob_dep";
if($arParams["CACHE_TIME"] == 0 || $obCache->StartDataCache($arParams["CACHE_TIME"], $cache_id))
{
	$arSelect = array("ID", "TIMESTAMP_X", "NAME", "UF_HEAD", "IBLOCK_SECTION_ID", 'SORT', 'PICTURE', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'DEPTH_LEVEL', 'DESCRIPTION', 'DESCRIPTION_TYPE', 'DETAIL_PICTURE');
	$dbRes = CIBlockSection::GetList(
		array('ID' => 'ASC'), 
		array('IBLOCK_ID' => COption::GetOptionInt('intranet', 'iblock_structure'), 'GLOBAL_ACTIVE'=>'Y'), 
		false, 
		$arSelect
	);

	$arDepartments = array();
	while($arDep = $dbRes->Fetch())
	{
		$arDep['URL'] = str_replace('#ID#', $arDep['ID'], $arParams['PATH_TO_COMPANY_DEPARTMENT']);
		$arDepartments[$arDep["ID"]] = $arDep;
	}
	$arResult["deps"] = $arDepartments;
	
	if($arParams["CACHE_TIME"] > 0)
	{
		$obCache->EndDataCache(array(
			'arDepartments' => $arDepartments,
		)); 
	}
}
elseif($arParams["CACHE_TIME"] > 0)
{
	$vars = $obCache->GetVars();
	$arResult['deps'] = $vars['arDepartments'];
}


$arResult["users"] = $arUsers;

$this->IncludeComponentTemplate($componentPage);
?>