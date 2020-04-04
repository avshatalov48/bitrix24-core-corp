<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!CModule::IncludeModule("webdav")):
    ShowError(GetMessage("WD_WD_MODULE_IS_NOT_INSTALLED"));
    return 0;
elseif (!CModule::IncludeModule("iblock")):
    ShowError(GetMessage("WD_IB_MODULE_IS_NOT_INSTALLED"));
    return 0;
elseif (!CModule::IncludeModule("socialnetwork")):
    ShowError(GetMessage("WD_SN_MODULE_IS_NOT_INSTALLED"));
    return 0;
endif;


global $USER;

$arDefaultUrlTemplates404 = array(
    "USER_FILE_PATH" => 'company/personal/user/#USER_ID#/files/lib/#PATH#',
    "GROUP_FILE_PATH" => 'workgroups/group/#GROUP_ID#/files/#PATH#',
);

$modes = array(
    'group' => GetMessage('WD_GROUP'),
    'private' =>GetMessage('WD_PRIVATE'),
    'user' =>GetMessage('WD_USER'),
    'root' => ''
);

$arParams['SEF_MODE'] = $arParams['SEF_MODE']=='N'?'N':'Y';
$arParams['CACHE_TIME'] = intval($arParams['CACHE_TIME']);
$arParams["IBLOCK_USER_ID"] = intval($arParams["IBLOCK_USER_ID"]);
$arParams["IBLOCK_GROUP_ID"] = intval($arParams["IBLOCK_GROUP_ID"]);
if (strlen(trim($arParams["NAME_TEMPLATE"])) <= 0)
    $arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();
$cachePath = str_replace(array(":", "//"), "/", "/".SITE_ID."/".$componentName."/");
$arParams["EXPAND_ALL"] = "N";

$keys = array_keys($arParams["IBLOCK_OTHER_IDS"]);
foreach ($keys as $key)
{
    $id = intval($arParams["IBLOCK_OTHER_IDS"][$key]);
    if ($id > 0 && $id != $arParams['IBLOCK_USER_ID'] && $id != $arParams['IBLOCK_GROUP_ID'])
    {
        $arParams["IBLOCK_OTHER_IDS"][$key] = $id;
        $dbRes = CIBlock::GetByID($id);
        if ($dbRes && $arRes = $dbRes->Fetch())
        {
            $path = "/m".$arRes['LIST_PAGE_URL'];
            if (SubStr($path,0,1) != '/') $path = '/'.$path;
            if (SubStr($path,-1,1) != '/') $path .= '/';
            $path .= '#PATH#';
            if (SITE_ID == $arRes['LID'])
            {
                $arDefaultUrlTemplates404 = array('i'.$id => $path) + $arDefaultUrlTemplates404;
                $modes = array($id => $arRes['NAME']) + $modes;
            } else {
                unset($arParams["IBLOCK_OTHER_IDS"][$key]);
            }
        } else {
            unset($arParams["IBLOCK_OTHER_IDS"][$key]);
        }
    } else {
        unset($arParams["IBLOCK_OTHER_IDS"][$key]);
    }
}

$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams["SEF_URL_TEMPLATES"]);
unset($arUrlTemplates['SEF_FOLDER']);
$arParams = array_merge($arParams, $arUrlTemplates);

$currentUserID = $USER->GetID();

if (SubStr($arParams["GROUP_FILE_PATH"],0,1) != '/') $arParams["GROUP_FILE_PATH"] = '/'.$arParams["GROUP_FILE_PATH"];
if (SubStr($arParams["USER_FILE_PATH"],0,1) != '/') $arParams["USER_FILE_PATH"] = '/'.$arParams["USER_FILE_PATH"];

$rIBGroup = CIBlock::GetList(Array(), Array(
	"ID"=>$arParams["IBLOCK_GROUP_ID"],
	"CHECK_PERMISSIONS" => "N"
));
if (!($arIBGroup = $rIBGroup->Fetch()) && CBXFeatures::IsFeatureEnabled("Workgroups"))
{
    ShowError(GetMessage("WD_IB_GROUP_IS_NOT_FOUND"));
    return 0;
}
$rIBUser = CIBlock::GetList(Array(), Array(
	"ID" => $arParams["IBLOCK_USER_ID"],
	"CHECK_PERMISSIONS" => "N"
));
if (!($arIBUser = $rIBUser->Fetch()))
{
    ShowError(GetMessage("WD_IB_USER_IS_NOT_FOUND"));
    return 0;
}

$file = trim(preg_replace("'[\\\\/]+'", "/", ($_SERVER['DOCUMENT_ROOT']."/bitrix/components/bitrix/socialnetwork_group/lang/".LANGUAGE_ID."/include/webdav.php")));
if (file_exists($file))
    __IncludeLang($file);
else
{
    CHTTP::SetStatus('404 Not Found');
    ShowError(GetMessage("WD_SOCNET_LANG_NOT_FOUND"));
    return 0;
}

$arVariables = array();
$sPath = false;
$mode = false;

if ($arParams["SEF_MODE"] === "Y")
{
    $requestURL = $APPLICATION->GetCurPage();
    $arParams["SEF_FOLDER"] = str_replace("\\", "/", $arParams["SEF_FOLDER"]);
    if ($arParams["SEF_FOLDER"] != "/")
        $arParams["SEF_FOLDER"] = "/".Trim($arParams["SEF_FOLDER"], "/ \t\n\r\0\x0B")."/";
    if (!preg_match("'/$'", $requestURL)) $currentPageUrl = $currentPageUrl.'/';
    $currentPageUrl = SubStr($requestURL, StrLen($arParams["SEF_FOLDER"]));
    if ($currentPageUrl == false) $currentPageUrl = '/';
} else {
    ShowError(GetMessage("WD_NOT_SEF_MODE"));
    return 0;
}

if (!preg_match("'^/'", $currentPageUrl)) $currentPageUrl = '/'.$currentPageUrl;
if (!preg_match("'/$'", $currentPageUrl)) $currentPageUrl = $currentPageUrl.'/';

foreach ($modes as $modeName=>$path)
{
    if (preg_match_all("'/{$path}(.*)'", $currentPageUrl, $arValues))
    {
        $mode = $modeName;
        $localPath = $arValues[1][0];
        break;
    }
}

if (isset($_SERVER['HTTP_DESTINATION']))
{
    $_SERVER['HTTP_DESTINATION'] = CWebDavBase::_udecode($_SERVER['HTTP_DESTINATION']);
    $pu = parse_url($_SERVER['HTTP_DESTINATION']);
    $pu['path'] = substr($pu['path'],  strlen($arParams['SEF_FOLDER']));
    foreach ($modes as $modeName=>$path)
    {
        if (preg_match_all("'/{$path}(.*)'", $pu['path'], $arValues))
        {
            $destPath = $arValues[1][0];
            break;
        }
    }
}

$rootPath = CWebDavBase::_udecode($arParams['SEF_FOLDER'].$modes[$mode]);
$fullPath = $rootPath.$localPath;
if ($currentPageUrl != '/') $currentPageUrl = rtrim($currentPageUrl, '/');

include_once("functions.php");
$folderTree = array();
$depth = 0;

// OTHER SHARES
// ******************************************************
if (IntVal($mode) > 0 || in_array($mode, Array("group", "private", "user")))
{
	$tUrl = $rootPath.$localPath;
	$tUrl = substr($tUrl, 0, strrpos($tUrl , '/'));
	$tUrl = substr($tUrl, 0, strrpos($tUrl , '/'));
	if($localPath == "/")
		$tUrl = $arParams["SEF_FOLDER"];
	$folderTree[] = array('NAME'=>GetMessage('WD_ROOT'), 'PATH' => $tUrl, 'DEPTH_LEVEL' => -1, "TYPE" => "up");
}

if ($mode == 'root')
{
    foreach($arParams['IBLOCK_OTHER_IDS'] as $id)
    {
        if (isset($modes[$id]))
        {
            $path = $arParams['SEF_FOLDER'].$modes[$id].'';
            $folderTree[] = array('NAME' => _getName($path), 'PATH' => $path, 'DEPTH_LEVEL' => $depth, 'MODE' => 'remote', "TYPE" => "folder");
        }
    }
}

if (intval($mode) > 0)
{
    $obOther = new CWebDavIblock($mode, _uencode($localPath), $arParams);
    if (!empty($obOther->arError))
    {
        ShowError($obOther->arError['text']);
        return false;
    }
	$options = array("path" => $localPath, "depth" => 1);
	$res = $obOther->PROPFIND($options, $files, array("return" => "array", "get_clones" => "N"));

	$obOther->IsDir($options);
	if ($obOther->arParams['is_file'])
	{
		$APPLICATION->RestartBuffer();
		$obOther->base_GET();
		die();
	}

	foreach($res["RESULT"] as $val)
	{
		if($val["~NAME"] != ".Trash")
		{
			$tmp = array('NAME' => $val["NAME"], 'PATH' => $rootPath.$val["PATH"], 'DEPTH_LEVEL' => $val["DEPTH_LEVEL"]);
			if($val["TYPE"] == "E")
			{
				$tmp["TYPE"] = "file";
				$tmp["FILE_EXTENTION"] = htmlspecialcharsbx(strtolower(strrchr($val['NAME'] , '.')));
			}
			else
			{
				$tmp["TYPE"] = "folder";
			}
			$folderTree[] = $tmp;
		}
	}
}

if (CBXFeatures::IsFeatureEnabled("Workgroups"))
{
    // WORKGROUPS
    // ****************************************************
    if ($mode == 'root' && $USER->IsAuthorized())
    {
        $path = $arParams['SEF_FOLDER'].$modes['group'].'';
        $folderTree[] = array('NAME' => _getName($path), 'PATH' => $path, 'DEPTH_LEVEL' => $depth, 'MODE' => 'local');
    }

    if ($mode == 'group')
    {
        // get all workgroup sections with files
        $CACHE_ID = SITE_ID . '|' . $requestURL . '|' . $currentUserID . '|' . (CWebDavBase::IsDavHeaders('check_all')?'D':'W') . '|GROUPS';
        $groupCache = new CPHPCache;
        if ($groupCache->InitCache($arParams["CACHE_TIME"], $CACHE_ID, $cachePath))
        {
            $vars = $groupCache->GetVars();
            $arGroupSectionIDs = $vars['GROUP_SECTION_IDS'];
            $currentUserGroups = $vars['CURRENT_USER_GROUPS'];
        }
        else
        {
            $arFilter = array(
                "IBLOCK_ID" => $arParams["IBLOCK_GROUP_ID"],
                "SECTION_ID" => 0,
				"CHECK_PERMISSIONS" => "N"
            );
            $arGroupSections = array();
            $arGroupSectionIDs = array();
            $dbSection = CIBlockSection::GetList(array(), $arFilter, false, array('ID', 'SOCNET_GROUP_ID'));
            while ($arGroupSection = $dbSection->Fetch())
            {
                $arGroupSections[$arGroupSection['ID']] = $arGroupSection;
                $arGroupSectionIDs[$arGroupSection['ID']] = $arGroupSection['SOCNET_GROUP_ID'];
            }
            // get all user workgroups
            $currentUserGroups=array();
            $userGroupIDs = array();
            $db_res = CSocNetUserToGroup::GetList(
                array("GROUP_NAME" => "ASC"),
                array( "USER_ID" => $currentUserID,),
                false,
                false,
                array("GROUP_ID", "GROUP_NAME", "GROUP_ACTIVE", "ROLE")
            );
            while ($res = $db_res->GetNext())
            {
                $currentUserGroups[$res["GROUP_ID"]] = $res;
                $userGroupIDs[] = $res["GROUP_ID"];
            }
            // intersect result - user groups which has files
            $arGroupSectionIDs = array_intersect($arGroupSectionIDs, $userGroupIDs);
            if ($groupCache->StartDataCache())
                $groupCache->EndDataCache(array('GROUP_SECTION_IDS' => $arGroupSectionIDs, 'CURRENT_USER_GROUPS' => $currentUserGroups));
        }
        unset($groupCache);

        $arLocalPath = explode('/', trim($localPath, '/'));
        if (empty($arLocalPath[0])) unset($arLocalPath[0]);
        if (sizeof($arLocalPath) > 0)
        {
            $groupName = GetMessage('SONET_GROUP_PREFIX').$arLocalPath[0];
            $arFilter = array(
                "IBLOCK_ID" => $arParams["IBLOCK_GROUP_ID"],
                "NAME" => $groupName,
				"CHECK_PERMISSIONS" => "N"
            );
            $dbSection = CIBlockSection::GetList(array(), $arFilter, false, array('ID', 'SOCNET_GROUP_ID'));
            if ($arGroupSection = $dbSection->Fetch())
            {
                $sectionID = $arGroupSection['ID'];
                $arVariables['GROUP_ID'] = $arGroupSection['SOCNET_GROUP_ID'];
            } else {
                CHTTP::SetStatus('404 Not Found');
                ShowError(GetMessage("WD_GROUP_SECTION_FILES_NOT_FOUND"));
                return 0;
            }

            $groupPerms = CIBlockWebdavSocnet::GetUserMaxPermission( 'group', $arVariables['GROUP_ID'], $currentUserID, $arParams['IBLOCK_GROUP_ID']);
            foreach (array('PERMISSION', 'CHECK_CREATOR') as $propName)
                $arParams[$propName] = $groupPerms[$propName];

            $arParams["DOCUMENT_TYPE"] = array("webdav", "CIBlockDocumentWebdavSocnet", "iblock_".$arParams['IBLOCK_GROUP_ID']."_group_".intVal($arVariables['GROUP_ID']));
            $obGroup = new CWebDavIblock($arParams['IBLOCK_GROUP_ID'], $localPath, $arParams);
            $obGroup->SetRootSection($sectionID);

			$cnt = count($arLocalPath);
			if($cnt > 1)
			{
				$path = "";
				foreach($arLocalPath as $k => $v)
				{
					if($k > 0)
						$path .= "/".$v;
				}
			}
			else
				$path = "/";

			$options = array("path" => $path, "depth" => 1);
			$res = $obGroup->PROPFIND($options, $files, array("return" => "array", "get_clones" => "Y", "FILTER" => Array()));

			$obGroup->IsDir($options);
			if ($obGroup->arParams['is_file'])
			{
				$APPLICATION->RestartBuffer();
				$obGroup->base_GET();
				die();
			}

			foreach($res["RESULT"] as $val)
			{
				if($val["~NAME"] != ".Trash")
				{
					$tmp = array('NAME' => $val["NAME"], 'PATH' => $rootPath."/".$arLocalPath[0].$val["PATH"], 'DEPTH_LEVEL' => $val["DEPTH_LEVEL"]);
					if($val["TYPE"] == "E")
					{
						$tmp["TYPE"] = "file";
						$tmp["FILE_EXTENTION"] = htmlspecialcharsbx(strtolower(strrchr($val['NAME'] , '.')));
					}
					else
					{
						$tmp["TYPE"] = "folder";
					}
					$folderTree[] = $tmp;
				}
			}
        } else {
            // group list
            $groupTree = array();
            $CACHE_ID = SITE_ID . '|' . $requestURL . '|' . $currentUserID . '|' .(CWebDavBase::IsDavHeaders('check_all')?'D':'W') .'|GROUPSECTIONS';
            $groupCache = new CPHPCache;
            if ($groupCache->InitCache($arParams["CACHE_TIME"], $CACHE_ID, $cachePath))
            {
                $vars = $groupCache->GetVars();
                $groupTree = $vars['GROUP_TREE'];
            }
            else
            {
                foreach ($arGroupSectionIDs as $sectionID=>$groupID)
                {
                    if ($currentUserGroups[$groupID]["GROUP_ACTIVE"] != 'Y') continue;
                    if (!CSocNetFeatures::IsActiveFeature( SONET_ENTITY_GROUP, $groupID, "files")) continue;
                    $groupPerms = CIBlockWebdavSocnet::GetUserMaxPermission( 'group', $groupID, $currentUserID, $arParams['IBLOCK_GROUP_ID']);
                    if ($groupPerms["PERMISSION"] < "R") continue;
                    $path = $currentUserGroups[$groupID]['GROUP_NAME'];
                    $path = $rootPath . '/' . $path;
                    $groupTree[] = array('NAME' => _getName($path), 'PATH' => $path, 'DEPTH_LEVEL' => 1, 'MODE' => 'remote');
                }
                if ($groupCache->StartDataCache())
                    $groupCache->EndDataCache(array('GROUP_TREE' => $groupTree));
            }
            unset($groupCache);
            usort($groupTree, "_wd_aggregator_sort");
            $folderTree = array_merge($folderTree, $groupTree);
        }
    }
}

// PERSONAL DOCS
// ****************************************************
if ($mode == 'root')
{
    if (CSocNetFeatures::IsActiveFeature( SONET_ENTITY_USER, $currentUserID, "files"))
    {
        $path = $arParams['SEF_FOLDER'].$modes['private'].'';
        $folderTree[] = array('NAME' => _getName($path), 'PATH' => $path, 'DEPTH_LEVEL' => 0, 'MODE' => 'remote');
    }
}
if ($mode == 'private')
{
    if (CSocNetFeatures::IsActiveFeature( SONET_ENTITY_USER, $currentUserID, "files"))
    {
        $ownerPerms = CIBlockWebdavSocnet::GetUserMaxPermission( "user", $currentUserID, $currentUserID, $arParams['IBLOCK_USER_ID']);
        if ($ownerPerms >= "R")
        {
            $arLocalPath = explode('/', trim($localPath, '/'));
            if (empty($arLocalPath[0])) unset($arLocalPath[0]);
            $arFilter = array(
                "IBLOCK_ID" => $arParams["IBLOCK_USER_ID"],
                "SOCNET_GROUP_ID" => false,
                "SECTION_ID" => 0,
				"CHECK_PERMISSIONS" => "N",
                "CREATED_BY" => $currentUserID
            );

            $db_res = CIBlockSection::GetList(array(), $arFilter);
            if ($db_res && $res = $db_res->Fetch())
            {
                $sectionID = $res['ID'];
                $obGroup = new CWebDavIblock($arParams['IBLOCK_USER_ID'], $localPath, $arParams);
                $obGroup->SetRootSection($sectionID);
                $currentPageUrl = str_replace(array('#USER_ID#', '#PATH#'), array($currentUserID, ''), $arParams["USER_FILE_PATH"]);
                foreach (array('PERMISSION', 'CHECK_CREATOR') as $propName)
                    $arParams[$propName] = $ownerPerms[$propName];
                $arParams["DOCUMENT_TYPE"] = array("webdav", "CIBlockDocumentWebdavSocnet", "iblock_".$arParams['IBLOCK_USER_ID']."_user_".intVal($currentUserID));
				$cnt = count($arLocalPath);
				if($cnt > 0)
				{
					$path = "";
					foreach($arLocalPath as $k => $v)
					{
							$path .= "/".$v;
					}
				}
				else
					$path = "/";
				$options = array("path" => $path, "depth" => 1);
				$res = $obGroup->PROPFIND($options, $files, array("return" => "array", "get_clones" => "Y", "FILTER" => Array()));

				$obGroup->IsDir($options);
				if ($obGroup->arParams['is_file'])
				{
					$APPLICATION->RestartBuffer();
					$obGroup->base_GET();
					die();
				}

				foreach($res["RESULT"] as $val)
				{
					if($val["~NAME"] != ".Trash")
					{
						$tmp = array('NAME' => $val["NAME"], 'PATH' => $rootPath.$val["PATH"], 'DEPTH_LEVEL' => $val["DEPTH_LEVEL"]);
						if($val["TYPE"] == "E")
						{
							$tmp["TYPE"] = "file";
							$tmp["FILE_EXTENTION"] = htmlspecialcharsbx(strtolower(strrchr($val['NAME'] , '.')));
						}
						else
						{
							$tmp["TYPE"] = "folder";
						}
						$folderTree[] = $tmp;
					}
				}
            }
        }
    }
}

// SCAN USERS
// ****************************************************
if ($mode == 'root')
{
    $arFilter = array(
        "IBLOCK_ID" => $arParams["IBLOCK_USER_ID"],
        "SOCNET_GROUP_ID" => false,
        "SECTION_ID" => 0,
    );
    if (CSocNetFeatures::IsActiveFeature( SONET_ENTITY_USER, $currentUserID, "files") && CIBlockSection::GetCount($arFilter) > 0)
    {
        $path = $arParams['SEF_FOLDER'].$modes['user'].'';
        $folderTree[] = array('NAME' => _getName($path), 'PATH' => $path, 'DEPTH_LEVEL' => 0, 'MODE' => 'local');
    }
}
if ($mode == 'user')
{
    $arLocalPath = explode('/', trim($localPath, '/'));
    if (empty($arLocalPath[0])) unset($arLocalPath[0]);
    if (sizeof($arLocalPath) > 0)
    {
        $userName = $arLocalPath[0];
        $userFilter = array();
        if (strpos($userName, '(') !== false)
        {
            $userFilter = array('LOGIN_EQUAL' => trim($userName, '()'));
        } else {
            $userFilter = array('NAME' => $userName);
        }

        $dbUser = CUser::GetList($by, $order, $userFilter);
        if (($dbUser !== false) && $arUser = $dbUser->Fetch())
        {
            $userID = $arUser['ID'];
            $userLogin = $arUser['LOGIN'];
        } else {
            CHTTP::SetStatus('404 Not Found');
            ShowError(GetMessage("WD_USER_NOT_FOUND"));
            return 0;
        }

        $arFilter = array(
            "IBLOCK_ID" => $arParams["IBLOCK_USER_ID"],
            "SOCNET_GROUP_ID" => false,
            "SECTION_ID" => 0,
			"CHECK_PERMISSIONS" => "N",
            "CREATED_BY" => $userID,
        );

        $dbSection = CIBlockSection::GetList(array(), $arFilter, false, array('ID'));
        if ($arUserSection = $dbSection->Fetch())
        {
            $sectionID = $arUserSection['ID'];
        } else {
            CHTTP::SetStatus('404 Not Found');
            ShowError(GetMessage("WD_USER_SECTION_FILES_NOT_FOUND"));
            return 0;
        }

        $userPerms = CIBlockWebdavSocnet::GetUserMaxPermission( 'user', $userID, $currentUserID, $arParams['IBLOCK_USER_ID']);
        foreach (array('PERMISSION', 'CHECK_CREATOR') as $propName)
            $arParams[$propName] = $userPerms[$propName];
        $arParams["DOCUMENT_TYPE"] = array("webdav", "CIBlockDocumentWebdavSocnet", "iblock_".$arParams['IBLOCK_USER_ID']."_user_".intVal($userID));
        $obGroup = new CWebDavIblock($arParams['IBLOCK_USER_ID'],  $localPath, $arParams);
        $obGroup->SetRootSection($sectionID);

		$cnt = count($arLocalPath);
		if($cnt > 1)
		{
			$path = "";
			foreach($arLocalPath as $k => $v)
			{
				if($k > 0)
					$path .= "/".$v;
			}
		}
		else
			$path = "/";

		$options = array("path" => $path, "depth" => 1);
		$res = $obGroup->PROPFIND($options, $files, array("return" => "array", "get_clones" => "Y", "FILTER" => Array()));
		$obGroup->IsDir($options);
		if ($obGroup->arParams['is_file'])
		{
			$APPLICATION->RestartBuffer();
			$obGroup->base_GET();
			die();
		}

		foreach($res["RESULT"] as $val)
		{
			if($val["~NAME"] != ".Trash")
			{
				$tmp = array('NAME' => $val["NAME"], 'PATH' => $rootPath."/".$arLocalPath[0].$val["PATH"], 'DEPTH_LEVEL' => $val["DEPTH_LEVEL"]);
				if($val["TYPE"] == "E")
				{
					$tmp["TYPE"] = "file";
					$tmp["FILE_EXTENTION"] = htmlspecialcharsbx(strtolower(strrchr($val['NAME'] , '.')));
				}
				else
				{
					$tmp = array('NAME' => $val["NAME"], 'PATH' => $rootPath."/".$arLocalPath[0].$val["PATH"], 'DEPTH_LEVEL' => $val["DEPTH_LEVEL"]);
					$tmp["TYPE"] = "folder";
				}
				$folderTree[] = $tmp;
			}
		}
    } else {
        // user list
        $userTree = array();
        $CACHE_ID = SITE_ID . '|' . $requestURL . '|' . $currentUserID . '|' . (CWebDavBase::IsDavHeaders('check_all')?'D':'W') .'|USERLIST';
        $userCache = new CPHPCache;
        if ($userCache->InitCache($arParams["CACHE_TIME"], $CACHE_ID, $cachePath))
        {
            $vars = $userCache->GetVars();
            $userTree = $vars['USER_TREE'];
        }
        else
        {
            $arFilter = array(
                "IBLOCK_ID" => $arParams["IBLOCK_USER_ID"],
                "SOCNET_GROUP_ID" => false,
                "SECTION_ID" => 0,
				"CHECK_PERMISSIONS" => "N",
            );
            $dbSection = CIBlockSection::GetList(array(), $arFilter);
            while ($arSection = $dbSection->Fetch())
            {
                $userID = $arSection['CREATED_BY'];
                if (!CSocNetFeatures::IsActiveFeature( SONET_ENTITY_USER, $userID, "files")) continue;
                $userPerms = CIBlockWebdavSocnet::GetUserMaxPermission( 'user', $userID, $currentUserID, $arParams['IBLOCK_USER_ID']);
                if ($userPerms["PERMISSION"] < "R")
                    continue;
                $dbUser = CUser::GetByID($userID);
                if ($arUser = $dbUser->Fetch())
                {
                    if ($arUser['ACTIVE'] != 'Y') continue;
                }

                $tpl = preg_replace(array("/#NOBR#/","/#\/NOBR#/"), array("",""), $arParams['NAME_TEMPLATE']);
                $name = CUser::FormatName($tpl, $arUser, false, false);

                if ($name == ' ' || $name !== htmlspecialcharsbx($name) || (empty($arUser['NAME']) && empty($arUser['SECOND_NAME'])) )
                    $name = '('.$arUser['LOGIN'].')';
                $path = $rootPath . '/' . $name;
                $userTree[] = array('NAME' => _getName($path), 'PATH' => $path, 'DEPTH_LEVEL' => 1, 'MODE'=>'remote');
            }
            if ($userCache->StartDataCache())
                $userCache->EndDataCache(array('USER_TREE' => $userTree));
        }
        unset($userCache);
        usort($userTree, "_wd_aggregator_sort");
        $folderTree = array_merge($folderTree, $userTree);
    }
}
?>

<?
$ob = new CWebDavVirtual($folderTree, '/', $arParams);
$arResult['OBJECT'] = $ob;
$arResult['STRUCTURE'] = $folderTree;
$this->IncludeComponentTemplate();
?>
