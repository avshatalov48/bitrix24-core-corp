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

if(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk'))
{
	$APPLICATION->IncludeComponent(
		"bitrix:disk.aggregator",
		"",
		Array(
			"SEF_MODE" => "Y",
			"CACHE_TIME" => 3600,
		),
		false
	);
	return false;
}

global $USER;

$arDefaultUrlTemplates404 = array(
	"USER_FILE_PATH" => 'company/personal/user/#USER_ID#/files/lib/#PATH#',
	"GROUP_FILE_PATH" => 'workgroups/group/#GROUP_ID#/files/#PATH#',
	"USER_VIEW" => "/company/personal/user/#USER_ID#/",
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
$arParams["BASE_URL"] = $arParams["~SEF_FOLDER"];
$arParams['BASE_URL'] = str_replace(":443", "", rtrim($arParams['BASE_URL'], '/'));
$arParams["BASE_URL"] = ($APPLICATION->IsHTTPS() ? 'https' : 'http').'://'.str_replace("//", "/", $_SERVER['HTTP_HOST']."/".$arParams['BASE_URL']."/");
$arResult["URL_TEMPLATES"]['user_view'] = CComponentEngine::MakePathFromTemplate($arParams["USER_VIEW_URL"], array("#USER_ID#" => CUser::GetID()));
if (!isset($arParams["IBLOCK_OTHER_IDS"]) || !is_array($arParams["IBLOCK_OTHER_IDS"]))
	$arParams["IBLOCK_OTHER_IDS"] = array();

$keys = array_keys($arParams["IBLOCK_OTHER_IDS"]);
foreach ($keys as $key)
{
	$id = intval($arParams["IBLOCK_OTHER_IDS"][$key]);
	if (
		($id > 0)
		&& ($id != $arParams['IBLOCK_USER_ID'])
		&& ($id != $arParams['IBLOCK_GROUP_ID'])
	)
	{
		$arParams["IBLOCK_OTHER_IDS"][$key] = $id;
		$dbRes = CIBlock::GetByID($id);
		if ($dbRes && $arRes = $dbRes->Fetch())
		{
			$path = str_replace(array('///', '//'), '/', str_replace('#SITE_DIR#', SITE_DIR, $arRes['LIST_PAGE_URL']));
			if (SubStr($path,0,1) != '/') $path = '/'.$path;
			if (SubStr($path,-1,1) != '/') $path .= '/';
			$path .= '#PATH#';

			$arSites = array();
			$rSites = CIBlock::GetSite($id);
			while($arSite = $rSites->Fetch())
				$arSites[$arSite['LID']] = $arSite;

			if (isset($arSites[SITE_ID]))
			{
				$arDefaultUrlTemplates404 = array('i'.$id => $path) + $arDefaultUrlTemplates404;
				$modes = array($id => rawurlencode(str_replace("/", "", $arRes['NAME']))) + $modes;
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

include_once(str_replace(array("\\", "//"), "/", dirname(__FILE__)."/functions.php"));

$arVariables = array();
$sPath = false;
$mode = false;

if ($arParams["SEF_MODE"] === "Y")
{
	$requestURL = $APPLICATION->GetCurPage(false);
	$arParams["SEF_FOLDER"] = str_replace("\\", "/", $arParams["SEF_FOLDER"]);
	if ($arParams["SEF_FOLDER"] != "/")
		$arParams["SEF_FOLDER"] = "/".Trim($arParams["SEF_FOLDER"], "/ \t\n\r\0\x0B")."/";
} else {
	ShowError(GetMessage("WD_NOT_SEF_MODE"));
	return 0;
}

if (isset($_REQUEST['connect']))
{
	$this->IncludeComponentTemplate();
}
else
{
	$mode = 'root';
	$currentPageUrl = _wdFormatRequestUrl($requestURL, $arParams);
	$daw = (CWebDavBase::IsDavHeaders('check_all')?'D':'W');
	foreach ($modes as $modeName => $path)
	{
		$path = rawurldecode($path);
		if ($daw === 'D')
			$path = _wdCleanUpForbiddenSymbols($path);

		if (preg_match_all("(/".preg_quote($path)."/(.*))", $currentPageUrl, $arValues))
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

		$pu['path'] = _wdFormatRequestUrl($pu['path'], $arParams);

		foreach ($modes as $modeName => $path)
		{
			if ($daw === 'D')
				$path = _wdCleanUpForbiddenSymbols($path);

			if (preg_match_all("'/{$path}(.*)'", $pu['path'], $arValues))
			{
				$destPath = $arValues[1][0];
				break;
			}
		}
	}

	$rootPath = CWebDavBase::_udecode($arParams['SEF_FOLDER'].$modes[$mode]);

	if ($daw === 'D')
		$rootPath = _wdCleanUpForbiddenSymbols($rootPath);

	if ($currentPageUrl != '/')
		$currentPageUrl = rtrim($currentPageUrl, '/');

	$folderTree = array();
	$depth = 0;
	// OTHER SHARES
	// ******************************************************
	if ($mode == 'root')
	{
		foreach($arParams['IBLOCK_OTHER_IDS'] as $id)
		{
			if (isset($modes[$id]))
			{
				$path = $arParams['SEF_FOLDER'] . $modes[$id];
				$name = $arParams['SEF_FOLDER'] . urldecode($modes[$id]);
				$folderTree[] = array(
					'NAME' => _getName($name),
					'PATH' => _getPath($path, $arParams['SEF_FOLDER']),
					'DEPTH_LEVEL' => $depth,
					'MODE' => 'remote',
					'DOCCOUNT' => $USER->IsAdmin()? _getIBlockItemsCount($id) : false,
					'IB_MODE' => _getIBlockMode($id));
			}
		}
	}

	if (intval($mode) > 0)
	{
		// for copy/move methods
		if (isset($_SERVER['HTTP_DESTINATION']))
		{
			$arDestPath = explode('/', trim($destPath, '/'));
			if (empty($arDestPath[0])) unset($arDestPath[0]);
			if (sizeof($arDestPath) > 0)
			{
				$destName = $arDestPath[0];
			}
		}

		$obOther = new CWebDavIblock($mode, _uencode($localPath), $arParams);
		if (!empty($obOther->arError))
		{
			ShowError($obOther->arError['text']);
			return false; 
		}

		$obDavEventHandler = CWebDavSocNetEvent::GetRuntime();
		$obDavEventHandler->SetParams(array(
			'PATH_TO_USER' => $arResult["URL_TEMPLATES"]['user_view'],
			'PATH_TO_FILES_ELEMENT' => CIBlock::GetArrayByID($mode, "DETAIL_PAGE_URL"),
			'IBLOCK_ID' => $mode,
		));

		MakeDavRedirect($obOther, str_replace('#PATH#', '', $arUrlTemplates['i'.$mode]), $rootPath, $localPath, false);
	}

	// PERSONAL DOCS
	// ****************************************************
	if ($mode == 'private' || $mode == 'root')
	{
		if (CSocNetFeatures::IsActiveFeature( SONET_ENTITY_USER, $currentUserID, "files")) 
		{
			$path = $arParams['SEF_FOLDER'].$modes['private'].'';		
			$folderTree[] = array(
				'NAME' => _getName($path),
				'PATH' => _getPath($path, $arParams['SEF_FOLDER']),
				'DEPTH_LEVEL' => 0,
				'MODE' => 'remote',
				'DOCCOUNT' => $USER->IsAdmin()? _getIBlockItemsCount($arParams["IBLOCK_USER_ID"], null, 'user', $currentUserID) : false,
				'CLASS' => 'personal');
		}
	}
	if ($mode == 'private' || $arParams["EXPAND_ALL"] == "Y")
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
					$arParams["ATTRIBUTES"] = array('user_id' => CUser::GetID());
					$obGroup = new CWebDavIblock($arParams['IBLOCK_USER_ID'], $localPath, $arParams);
					$obGroup->SetRootSection($sectionID);
					$currentPageUrl = str_replace(array('#USER_ID#', '#PATH#'), array($currentUserID, ''), $arParams["USER_FILE_PATH"]);
					foreach (array('PERMISSION', 'CHECK_CREATOR') as $propName)
						$arParams[$propName] = $ownerPerms[$propName];
					$arParams["DOCUMENT_TYPE"] = array("webdav", "CIBlockDocumentWebdavSocnet", "iblock_".$arParams['IBLOCK_USER_ID']."_user_".intVal($currentUserID)); 

					$obDavEventHandler = CWebDavSocNetEvent::GetRuntime();
					$obDavEventHandler->SetSocnetVars(array(
						'PATH_TO_USER_FILES_ELEMENT' => $arParams["USER_FILE_PATH"],
					), array(
						'OBJECT' => $obGroup,
						'PATH_TO_USER' => $arResult["URL_TEMPLATES"]['user_view'],
						'FILES_USER_IBLOCK_ID' => $arParams["IBLOCK_USER_ID"],
					));

					MakeDavRedirect($obGroup, $currentPageUrl, $rootPath, $localPath, false);
				}
				elseif ($ownerPerms < "W")
				{
					CHTTP::SetStatus('404 Not Found');
					ShowError(GetMessage("WD_USER_SECTION_FILES_NOT_FOUND"));
					return 0;
				}
				else
				{
					__wd_check_uf_use_bp_property($arParams["IBLOCK_USER_ID"]); 

					$arFields = Array(
						"IBLOCK_ID" => $arParams["IBLOCK_USER_ID"],
						"ACTIVE" => "Y",
						"SOCNET_GROUP_ID" => false, 
						"IBLOCK_SECTION_ID" => 0, 
						"UF_USE_BP" => "N");

					$arFields["NAME"] = trim($USER->GetFormattedName(false));
					$arFields["NAME"] = trim(!empty($arFields["NAME"]) ? $arFields["NAME"] : $USER->GetLogin());
					$GLOBALS["UF_USE_BP"] = $arFields["UF_USE_BP"];
					$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("IBLOCK_".$arParams["IBLOCK_ID"]."_SECTION", $arFields);
					$bs = new CIBlockSection;
					if (!$bs->Add($arFields))
					{
						CHTTP::SetStatus('404 Not Found');
						$arParams["ERROR_MESSAGE"] = $bs->LAST_ERROR;
						return 0;
					}
					WDClearComponentCache(array(
						"webdav.element.edit", 
						"webdav.element.hist", 
						"webdav.element.upload", 
						"webdav.element.view", 
						"webdav.menu",
						"webdav.section.edit", 
						"webdav.section.list"));
					BXClearCache(true, $ob->CACHE_PATH);
					BXClearCache(true, $cachePath);
					$APPLICATION->RestartBuffer();
					LocalRedirect($_SERVER['REQUEST_URI']);
					die();
				}
			}
		}
	}

	if (CBXFeatures::IsFeatureEnabled("Workgroups"))
	{
		// WORKGROUPS 
		// ****************************************************
		if (($mode == 'root' || $mode == 'group') && $USER->IsAuthorized())
		{
			$path = $arParams['SEF_FOLDER'].$modes['group'].'';
			$folderTree[] = array(
				'NAME' => _getName($path),
				'PATH' => _getPath($path, $arParams['SEF_FOLDER']),
				'DEPTH_LEVEL' => $depth,
				'MODE' => 'local',
				'CLASS' => 'workgroups');
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
				$dbSection = CIBlockSection::GetList(array(), $arFilter, false, array('ID', 'NAME', 'SOCNET_GROUP_ID'));

				while ($arGroupSection = $dbSection->Fetch())
				{
					$arGroupSections[$arGroupSection['ID']] = $arGroupSection;
					$arGroupSectionIDs[$arGroupSection['ID']] = $arGroupSection['SOCNET_GROUP_ID'];
				}

				// get all user workgroups 
				$currentUserGroups = CIBlockWebdavSocnet::GetUserGroups($currentUserID);
				$userGroupIDs = array_keys($currentUserGroups);

				// intersect result - user groups which has files
				$arGroupSectionIDs = array_intersect($arGroupSectionIDs, $userGroupIDs);
				if ($groupCache->StartDataCache())
				{
					global $CACHE_MANAGER;
					$CACHE_MANAGER->StartTagCache($cachePath);
					$CACHE_MANAGER->RegisterTag("webdav_aggregator");
					$CACHE_MANAGER->EndTagCache();
					$groupCache->EndDataCache(array(
						'GROUP_SECTION_IDS' => $arGroupSectionIDs,
						'CURRENT_USER_GROUPS' => $currentUserGroups)
					);
				}
			} 
			unset($groupCache);

			$arLocalPath = _wdCleanUpForbiddenSymbols(explode('/', trim($localPath, '/')));
			if (empty($arLocalPath[0])) unset($arLocalPath[0]);
			if (sizeof($arLocalPath) > 0)
			{
				if (preg_match("/".GetMessage('SONET_GROUP')." (\d+)/", $arLocalPath[0], $matches) == 0)
				{
					$arFilter = array(
						"IBLOCK_ID" => $arParams["IBLOCK_GROUP_ID"],
						"SECTION_ID" => 0,
						"CHECK_PERMISSIONS" => "N"
					);

					$dbSection = CIBlockSection::GetList(array(), $arFilter, false, array('ID', 'NAME', 'SOCNET_GROUP_ID'));

					$sectionID = 0;
					while ($arGroupSection = $dbSection->Fetch())
					{
						$groupName = $arGroupSection['NAME'];
						if (strpos($groupName, GetMessage('SONET_GROUP_PREFIX')) === 0)
						{
							$groupName = substr($groupName, strlen(GetMessage('SONET_GROUP_PREFIX')));
						}
						$groupName = str_replace("/", "", _wdCleanUpForbiddenSymbols(htmlspecialcharsBack($groupName)));
						if ($groupName == $arLocalPath[0])
						{
							$sectionID = $arGroupSection['ID'];
							$arVariables['GROUP_ID'] = $arGroupSection['SOCNET_GROUP_ID'];
							break;
						}
					}
					if ($sectionID == 0)
					{
						CHTTP::SetStatus('404 Not Found');
						ShowError(GetMessage("WD_GROUP_SECTION_FILES_NOT_FOUND")); 
						return 0;
					}
				}
				else
				{
					$arFilter = array(
						"IBLOCK_ID" => $arParams["IBLOCK_GROUP_ID"],
						"CHECK_PERMISSIONS" => "N",
						"SOCNET_GROUP_ID" => $matches[1]
					);

					$dbSection = CIBlockSection::GetList(array(), $arFilter, false, array('ID', 'SOCNET_GROUP_ID'));

					if ($arGroupSection = $dbSection->Fetch())
					{
						$sectionID = $arGroupSection['ID'];
						$arVariables['GROUP_ID'] = $arGroupSection['SOCNET_GROUP_ID'];
					}
					else
					{
						CHTTP::SetStatus('404 Not Found');
						ShowError(GetMessage("WD_GROUP_SECTION_FILES_NOT_FOUND")); 
						return 0;
					}
				}

				// for copy/move methods
				if (isset($_SERVER['HTTP_DESTINATION']))
				{
					$arDestPath = explode('/', trim($destPath, '/'));
					if (empty($arDestPath[0])) unset($arDestPath[0]);
					if (sizeof($arDestPath) > 0)
					{
						$destName = $arDestPath[0];

						$_SERVER['HTTP_DESTINATION'] = str_replace($destName, GetMessage('SONET_GROUP_PREFIX').$destName , $_SERVER['HTTP_DESTINATION']);
						$arLocalPath[0] = GetMessage('SONET_GROUP_PREFIX').$destName;
					}
				}

				$groupPerms = CIBlockWebdavSocnet::GetUserMaxPermission( 'group', $arVariables['GROUP_ID'], $currentUserID, $arParams['IBLOCK_GROUP_ID']);
				foreach (array('PERMISSION', 'CHECK_CREATOR') as $propName)
					$arParams[$propName] = $groupPerms[$propName];
				$object = 'group';
				$arParams["DOCUMENT_TYPE"] = array("webdav", "CIBlockDocumentWebdavSocnet", "iblock_".$arParams['IBLOCK_GROUP_ID']."_group_".intVal($arVariables['GROUP_ID'])); 
				$arParams["ATTRIBUTES"] = array('group_id' => intVal($arVariables['GROUP_ID']));
				$obGroup = new CWebDavIblock($arParams['IBLOCK_GROUP_ID'], $localPath, $arParams);
				$obGroup->SetRootSection($sectionID); 
				$currentPageUrl = str_replace(array('#GROUP_ID#', '#PATH#'), array($arVariables['GROUP_ID'], ''), $arParams["GROUP_FILE_PATH"]);

				$obDavEventHandler = CWebDavSocNetEvent::GetRuntime();
				$obDavEventHandler->SetSocnetVars(array(
					'PATH_TO_GROUP_FILES_ELEMENT' => $arParams["GROUP_FILE_PATH"],
				), array(
					'OBJECT' => $obGroup,
					'PATH_TO_USER' => $arResult["URL_TEMPLATES"]['user_view'],
					'FILES_GROUP_IBLOCK_ID' => $arParams["IBLOCK_GROUP_ID"],
				));

				MakeDavRedirect($obGroup, $currentPageUrl, $rootPath.'/'.$arLocalPath[0], '/'.implode('/', array_slice($arLocalPath, 1)) . '/', false);
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
						if ($currentUserGroups[$groupID]["GROUP_ACTIVE"] != 'Y')
							continue;

						if (!CSocNetFeatures::IsActiveFeature( SONET_ENTITY_GROUP, $groupID, "files"))
							continue;

						$groupPerms = CIBlockWebdavSocnet::GetUserMaxPermission( 'group', $groupID, $currentUserID, $arParams['IBLOCK_GROUP_ID']);
						if ($groupPerms["PERMISSION"] < "R")
							continue;

						$groupName = str_replace("/", "", htmlspecialcharsBack($currentUserGroups[$groupID]['GROUP_NAME']));

						$path = $rootPath . '/' . urlencode($groupName);
						$name = $rootPath . '/' . str_replace("/", "", $groupName);
						$groupTree[] = array(
							'NAME' => _getName($name),
							'PATH' => _getPath($path, $arParams['SEF_FOLDER']),
							'DOCCOUNT' => $USER->IsAdmin()? _getIBlockItemsCount($arParams['IBLOCK_GROUP_ID'], $sectionID, 'group', $groupID) : false,
							'IB_MODE' => _getIBlockMode($arParams['IBLOCK_GROUP_ID'], 'group', $groupID),
							'DEPTH_LEVEL' => 1,
							'MODE' => 'remote');
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

	// SCAN USERS
	// ****************************************************
	if ($mode == 'root' || $mode == 'user')
	{
		$arFilter = array(
			"IBLOCK_ID" => $arParams["IBLOCK_USER_ID"],
			"SOCNET_GROUP_ID" => false,
			"SECTION_ID" => 0,
			"CHECK_PERMISSIONS" => 'N'
		);

		if (CSocNetFeatures::IsActiveFeature( SONET_ENTITY_USER, $currentUserID, "files") && CIBlockSection::GetCount($arFilter) > 0) 
		{
			$path = $arParams['SEF_FOLDER'].$modes['user'].'';
			$folderTree[] = array('NAME' => _getName($path), 'PATH' => _getPath($path, $arParams['SEF_FOLDER']), 'DEPTH_LEVEL' => 0, 'MODE' => 'local', 'CLASS' => 'users');
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
				$dbUser = CUser::GetList($by, $order, $userFilter);
			} else {
				//$userFilter = array('NAME' => $userName);
				$arName = explode(' ', $userName);
				foreach($arName as &$namePart)
				{
					$namePart = trim(trim($namePart), ".,-");
				}
				$dbUser = CUser::SearchUserByName($arName, '', false);
			}

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

			// for copy/move methods
			if (isset($_SERVER['HTTP_DESTINATION']))
			{
				$arDestPath = explode('/', trim($destPath, '/'));
				if (empty($arDestPath[0])) unset($arDestPath[0]);
				if (sizeof($arDestPath) > 0)
				{
					$destName = $arDestPath[0];

					$destFilter = array();
					if (strpos($destName, '(') !== false)
					{
						$destFilter = array('LOGIN_EQUAL' => trim($destName, '()')); 
					} else {
						$destFilter = array('NAME' => $destName);
					}

					$dbUser = CUser::GetList($by, $order, $destFilter);
					if (($dbUser !== false) && $arUser = $dbUser->Fetch())
					{
						$destID = $arUser['ID'];
						$destLogin = $arUser['LOGIN'];
					} else {
						ShowError(GetMessage("WD_USER_NOT_FOUND")); 
						return 0;
					}
					$_SERVER['HTTP_DESTINATION'] = str_replace($destName, $destLogin, $_SERVER['HTTP_DESTINATION']);
					$arLocalPath[0] = $destLogin;
				}
			}

			$userPerms = CIBlockWebdavSocnet::GetUserMaxPermission( 'user', $userID, $currentUserID, $arParams['IBLOCK_USER_ID']); 
			foreach (array('PERMISSION', 'CHECK_CREATOR') as $propName)
				$arParams[$propName] = $userPerms[$propName];
			$arParams["DOCUMENT_TYPE"] = array("webdav", "CIBlockDocumentWebdavSocnet", "iblock_".$arParams['IBLOCK_USER_ID']."_user_".intVal($userID)); 

			$basementPath = $rootPath.'/'.$arLocalPath[0];
			$obGroup = new CWebDavIblock($arParams['IBLOCK_USER_ID'],  $basementPath, $arParams);
			$obGroup->SetRootSection($sectionID); 
			$currentPageUrl = str_replace(array('#USER_ID#', '#PATH#'), array($userID, ''), $arParams["USER_FILE_PATH"]);
			MakeDavRedirect($obGroup, $currentPageUrl, $rootPath.'/'.$arLocalPath[0], '/'. implode('/', array_slice($arLocalPath, 1)) . '/', false);
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
					"CHECK_PERMISSIONS" => "N",
					"SECTION_ID" => 0,
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
					if ($dbUser && $arUser = $dbUser->Fetch())
					{
						if ($arUser['ACTIVE'] != 'Y') continue;
					}
					if(empty($arUser))
					{
						continue;
					}
					$iDocCount = $USER->IsAdmin()? _getIBlockItemsCount($arParams["IBLOCK_USER_ID"], $arSection["ID"], 'user', $userID) : false;
					//if ($iDocCount <= 0) continue;

					$tpl = preg_replace(array("/#NOBR#/","/#\/NOBR#/"), array("",""), $arParams['NAME_TEMPLATE']);
					$name = CUser::FormatName($tpl, $arUser, false, false);

					if ($name == ' ' || $name !== htmlspecialcharsbx($name) || (empty($arUser['NAME']) && empty($arUser['SECOND_NAME'])) )
						$name = '('.$arUser['LOGIN'].')';
					$path = $rootPath . '/' . $name;
					$userTree[] = array('NAME' => _getName($path), 'PATH' => _getPath($path, $arParams['SEF_FOLDER']), 'DEPTH_LEVEL' => 1, 'MODE'=>'remote', 'DOCCOUNT'=>$iDocCount, 'IB_MODE' => "");
				}
				if ($userCache->StartDataCache())
					$userCache->EndDataCache(array('USER_TREE' => $userTree));
			}
			unset($userCache);
			usort($userTree, "_wd_aggregator_sort");
			$folderTree = array_merge($folderTree, $userTree);
		}
	}

	$folderTree = array_merge(
		array(array('NAME'=>GetMessage('WD_ROOT'), 'PATH' => _getPath($arParams['SEF_FOLDER'], $arParams['SEF_FOLDER']), 'DEPTH_LEVEL' => -1)),
		$folderTree);

	$ob = new CWebDavVirtual($folderTree, '/', $arParams);
	MakeDavRedirect($ob, $currentPageUrl, $baseURL, $rootPath.'', true);
	$arResult['OBJECT'] = $ob;
	$arResult['STRUCTURE'] = $folderTree;
	$this->IncludeComponentTemplate();
}
?>
