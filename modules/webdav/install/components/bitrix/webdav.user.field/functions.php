<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * @param      $iblockId
 * @param      $sectionId
 * @param bool $returnFalseIfTrash
 * @return array|bool
 */
function WDUGetNavChainSections($iblockId, $sectionId, $returnFalseIfTrash = true)
{
	static $cache = array();
	if(isset($cache[$iblockId][$sectionId]))
	{
		return $cache[$iblockId][$sectionId];
	}
	$cache[$iblockId][$sectionId] = array();
	$db_nav = CIBlockSection::GetNavChain($iblockId, $sectionId);
	if ($db_nav && ($arSection = $db_nav->Fetch()))
	{
		if ($returnFalseIfTrash && $arSection['NAME'] == '.Trash') // not show items from trash
		{
			return false;
		}
		do
		{
			$cache[$iblockId][$sectionId][] = $arSection;
		}
		while ($arSection = $db_nav->Fetch());
	}

	return $cache[$iblockId][$sectionId];
}

function WDUFGetExtranetDir()
{
	global $APPLICATION, $USER;
	$URLPrefix = null;

	if ($URLPrefix == null)
	{
		$URLPrefix = '';
		if (
			CModule::IncludeModule('extranet')
			&& (strlen(CExtranet::GetExtranetSiteID()) > 0)
			&& $USER->IsAuthorized()
			&& !$USER->IsAdmin() &&
			!CExtranet::IsIntranetUser()
		)
		{
			$rsSites = CSite::GetByID(CExtranet::GetExtranetSiteID());
			if ($arExtranetSite  = $rsSites->Fetch())
			{
				$URLPrefix = $arExtranetSite["DIR"];
			}
		}
	}
	return $URLPrefix;
}

function WDUFLoadStyle()
{
	global $APPLICATION;
	static $styleLoaded = false;

	if (!$styleLoaded)
	{
		$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/wdif.css');
		$styleLoaded = true;
	}
}

function WDUFSetAccessToEdit(&$files, $contextEntity)
{
	global $USER;

	if(!is_array($files))
	{
		return;
	}
	static $rootDataForCurrentUser = null;
	if($rootDataForCurrentUser === null && $USER->getId())
	{
		$rootDataForCurrentUser = CWebDavIblock::getRootSectionDataForUser($USER->getId());
	}

	$listIdFile = $iBlockCacheID = array();
	foreach ($files as &$file)
	{
		if(!empty($file['ID']) && !empty($file['NAME']))
		{
			if($USER->getId() && !isset($file['IN_PERSONAL_LIB']))
			{
				$file['IN_PERSONAL_LIB'] = !CWebDavSymlinkHelper::isLink(CWebDavSymlinkHelper::ENTITY_TYPE_USER, $USER->getId(), array(
					'ID' => $file['IBLOCK_SECTION_ID'],
					'IBLOCK_ID' => $file['IBLOCK_ID'],
				));
				if(!$file['IN_PERSONAL_LIB'])
				{
					$file['IN_PERSONAL_LIB'] = (bool)CWebDavSymlinkHelper::getLinkData(CWebDavSymlinkHelper::ENTITY_TYPE_USER, $USER->getId(), array(
						'ID' => $file['IBLOCK_SECTION_ID'],
						'IBLOCK_ID' => $file['IBLOCK_ID'],
					));
				}
				if($file['IN_PERSONAL_LIB'])
				{
					$file['EXTERNAL_ID'] = "st{$rootDataForCurrentUser['IBLOCK_ID']}|{$rootDataForCurrentUser['SECTION_ID']}|f{$file['ID']}";
				}
			}

			//file can may editable if in personal lib (local edit) or if office doc - in services
			if(CWebDavEditDocGoogle::isEditable($file['NAME']) || !empty($file['IN_PERSONAL_LIB']))
			{
				$listIdFile[$file['ID']] = $file['ID'];
				$iBlockCacheID[] = $file['IBLOCK_ID'];
			}
		}
	}
	unset($file);

	if(empty($listIdFile))
	{
		return;
	}

	$obCache = new CPHPCache;
	$cachePath = SITE_ID."/webdav/inline";
	//user - entity - id of files
	$cacheID = md5('u' . (int)$USER->getId() . '_e' . $contextEntity . '_f' . implode('|', $listIdFile));

	if($obCache->InitCache(30*86400, $cacheID, $cachePath))
	{
		$vars = $obCache->GetVars();
		$result = $vars["RESULT"];
	}
	if (empty($result) && $obCache->StartDataCache())
	{
		$result = array();
		$rightsByElements = CIBlockElementRights::GetUserOperations($listIdFile);
		foreach ($files as &$file)
		{
			if(!isset($listIdFile[$file['ID']]))
			{
				continue;
			}

			if(!empty($rightsByElements[$file['ID']]) && !empty($rightsByElements[$file['ID']]['element_edit']))
			{
				$file['EDITABLE'] = true;
				$result[$file['ID']] = $file['ID'];
				continue;
			}
		}
		unset($file);

		global $CACHE_MANAGER;
		$CACHE_MANAGER->StartTagCache($cachePath);
		foreach ($iBlockCacheID as $ibID)
			$CACHE_MANAGER->RegisterTag("iblock_id_".$ibID);
		$CACHE_MANAGER->EndTagCache();
		$obCache->EndDataCache(array("RESULT" => $result));
	}
	else
	{
		foreach ($files as &$file)
		{
			if(!isset($listIdFile[$file['ID']]))
			{
				continue;
			}
			$file['EDITABLE'] = isset($result[$file['ID']]);
		}
	}
}

function WDUFUserFieldView(&$arParams, &$arResult)
{
	static $DROPPED = null;
	static $OLD_DROPPED = null;
	if (!(CModule::IncludeModule('iblock') && CModule::IncludeModule('webdav')))
		return false;

	if($DROPPED === null)
	{
		$DROPPED = CWebDavIblock::getDroppedMetaData();
		$DROPPED = $DROPPED['name'];

		$OLD_DROPPED = CWebDavIblock::getOldDroppedMetaData();
		$OLD_DROPPED = $OLD_DROPPED['name'];
	}

	global $APPLICATION, $USER_FIELD_MANAGER, $USER;
	static $arIBlock = array();
	$result = array();
	$arIBlockCacheID = array();
	$arValue = array();
	$isHistoryDocInComment = false; //from historical comment
	$versionHistoryDocInComment = $throughVersionComment = 0;
	$EVId = (is_array($arParams["arUserField"]) && $arParams["arUserField"]["ENTITY_VALUE_ID"] > 0 ?
		intval($arParams["arUserField"]["ENTITY_VALUE_ID"]) : 0);
	$arResult['VALUE'] = (is_array($arResult['VALUE']) ? $arResult['VALUE'] : array());

	if($arParams['arUserField']['USER_TYPE_ID'] == 'webdav_element_history')
	{
		$isHistoryDocInComment = true;
		//not multiple UF
		if(!empty($arResult['VALUE'][0]['id']))
		{
			$arValue[] = (int)$arResult['VALUE'][0]['id'];
			$versionHistoryDocInComment = (int)$arResult['VALUE'][0]['v'];
			$throughVersionComment = empty($arResult['VALUE'][1]['t_vers'])? 0 : (int)$arResult['VALUE'][1]['t_vers'];
		}
		$cacheID = md5(serialize($arResult['VALUE']));
	}
	else
	{
		foreach($arResult['VALUE'] as $val)
		{
			$val = intval($val);
			if ($val > 0)
			{
				$arValue[] = $val;
			}
		}
		$cacheID = ($EVId > 0 ? $EVId : md5(serialize($arValue)));
	}

	if(!empty($arParams["arUserField"]) && !empty($arParams["arUserField"]['ID']))
	{
		$cacheID = (string)$cacheID;
		$cacheID = $arParams["arUserField"]['ID'] . '_' . $cacheID;
	}

	if (sizeof($arValue) > 0)
	{
		// cache
		$obCache = new CPHPCache;
		$cachePath = SITE_ID."/webdav/inline";

		if($obCache->InitCache(30*86400, $cacheID, $cachePath))
		{
			$vars = $obCache->GetVars();
			$result = $vars["RESULT"];
		}
		if (empty($result) && $obCache->StartDataCache())
		{
			$ElementID = $arValue;
			if ($EVId > 0)
			{
				$ElementID = $USER_FIELD_MANAGER->GetUserFieldValue(
					$arParams["arUserField"]["ENTITY_ID"],
					$arParams["arUserField"]["FIELD_NAME"],
					$EVId);
				$ElementID = (empty($ElementID) ? $arValue : $ElementID);
				if($isHistoryDocInComment)
				{
					$ElementID = $ElementID[0]['id'];
				}
			}

			// check file exists
			$ibe = new CIBlockElement();
			$dbWDFile = $ibe->GetList(array(), array('ID' => $ElementID), false, false,
				array('ID', 'NAME', 'IBLOCK_SECTION_ID', 'IBLOCK_ID', 'IBLOCK_CODE', 'PROPERTY_' . CWebDavIblock::PROPERTY_VERSION ,'PROPERTY_WEBDAV_SIZE', 'PROPERTY_FILE', 'CREATED_BY', 'CREATED_USER_NAME', 'CREATED_BY_FORMATTED'));
			if ($dbWDFile)
			{
				$dbWDFile->SetNameTemplate($arParams['NAME_TEMPLATE']);
				while ($arWDFile = $dbWDFile->Fetch())
				{
					$id = intval($arWDFile['ID']);
					$arNavChain = array();

					if (!isset($arIBlock[$arWDFile['IBLOCK_ID']]))
					{
						$dbWDIBlock = CIBlock::GetList(array(), array('ID' => $arWDFile['IBLOCK_ID'], 'CHECK_PERMISSIONS' => 'N'));
						if ($dbWDIBlock && $arWDIBlock = $dbWDIBlock->Fetch())
							$arIBlock[$arWDFile['IBLOCK_ID']] = $arWDIBlock;
					}
					if (isset($arIBlock[$arWDFile['IBLOCK_ID']]))
					{
						$arWDIBlock = $arIBlock[$arWDFile['IBLOCK_ID']];
						$arIBlockCacheID[] = $arWDFile['IBLOCK_ID'];

						$arNavChain = WDUGetNavChainSections($arWDFile['IBLOCK_ID'], $arWDFile['IBLOCK_SECTION_ID']);
						if($arNavChain === false)
						{
							continue; // not show items from trash
						}
						// get path to document
						$detailPath = CWebDavIblock::LibOptions('lib_paths', true, $arWDFile['IBLOCK_ID']);
						$detailPath = (!!$detailPath ? $detailPath : $arWDIBlock['DETAIL_PAGE_URL']);
						$arPaths = WDUFGetPathOptions($detailPath, $arWDFile['IBLOCK_ID'], reset($arNavChain), $arWDFile);
						$isSocnet = ($arPaths["entity"] != "lib");

						$arWDFile['VIEW'] = $arPaths["path"];
						$arWDFile['HISTORY'] = CHTTP::urlAddParams($arPaths['view'], array('webdavForm' . $arWDFile['IBLOCK_ID'] . '_active_tab' => 'tab_history'));
						$arWDFile['EDIT'] = $arPaths["edit"];
						$arWDFile['DELETE_DROPPED'] = $arPaths["delete_dropped"];
						$arWDFile['PATH'] = $arPaths["history_get"];

						// 'breadcrumb'
						$arSectionsChain = array();
						//to link on element
						$userIBlockID = CWebDavIblock::LibOptions('user_files', false, SITE_ID);
						$groupIBlockID = CWebDavIblock::LibOptions('group_files', false, SITE_ID);

						$arUrlSectionsChain = array();
						$i = 0;
						foreach ($arNavChain as $res)
						{
							$name = $res["NAME"];
							if (($i == 0) && !!$res["SOCNET_GROUP_ID"] && CModule::IncludeModule('socialnetwork') && strlen(GetMessage('SONET_GROUP_PREFIX')) > 0)
							{
								if ($name == GetMessage('SONET_GROUP_PREFIX')) // old bug with empty folder name in group
								{
									$arGroup = CSocNetGroup::GetByID($res["SOCNET_GROUP_ID"]);
									$name = GetMessage("SONET_GROUP_PREFIX").$arGroup['NAME'];
								}
							}

							//drop prefix storage name (1st level in section tree) if user or groups file. If shared docs - don't
							if($i != 0 || !$isSocnet)
							{
								$arUrlSectionsChain[] = $name;
							}
							if ($name != $DROPPED)
							{
								$arSectionsChain[] = $name;
								$i++;
							}

							if ($name == $DROPPED || $name == $OLD_DROPPED)
							{
								//disable local edit if file id .Dropped
								$arWDFile['IN_PERSONAL_LIB'] = false;
							}

						}

						if ($arSectionsChain[$i] == $DROPPED)
						{
							$arWDFile['NAVCHAIN'] = GetMessage('WDUF_ATTACHED_TO_MESSAGE');
						}
						else
						{
							if ($userIBlockID && $groupIBlockID)
							{
								if (($arWDFile['IBLOCK_ID'] != $userIBlockID['id']) && ($arWDFile['IBLOCK_ID'] != $groupIBlockID['id']))
								{
									$name = CIBlock::GetArrayByID($arWDFile['IBLOCK_ID'], 'NAME');
									array_unshift($arSectionsChain, $name);
								}
							}

							$arWDFile['NAVCHAIN'] = implode("/", $arSectionsChain);
						}

						$arUrlSectionsChain[] = $arWDFile["NAME"];
						$arWDFile['VIEW'] .= implode('/', $arUrlSectionsChain);
						//non urnencoded
						$arWDFile['RELATIVE_PATH'] = $arWDFile['VIEW'];
						$arWDFile['VIEW'] = CHTTP::urnEncode($arWDFile['VIEW']);


						// extension
						$name = $arWDFile['NAME'];
						$ext = '';
						$dotpos = strrpos($name, ".");
						if (($dotpos !== false) && ($dotpos+1 < strlen($name)))
							$ext = substr($name, $dotpos+1);
						if (strlen($ext) < 3 || strlen($ext) > 5)
							$ext = '';
						$arWDFile['EXTENSION'] = $ext;

						// size
						$arWDFile['SIZE'] = 0;
						if ($arWDFile['PROPERTY_WEBDAV_SIZE_VALUE'])
							$arWDFile['SIZE'] = CFile::FormatSize(intval($arWDFile['PROPERTY_WEBDAV_SIZE_VALUE']), 0);

						// file
						$arWDFile['FILE'] = array();
						if ($arWDFile['PROPERTY_FILE_VALUE'])
						{
							$arWDFile['FILE'] = CFile::GetFileArray($arWDFile['PROPERTY_FILE_VALUE']);
						}

						if($isHistoryDocInComment)
						{
							$arWDFile['PATH'] = $arWDFile['PATH'] . "?toWDController=1&ncc=1&downloadHistory=1&id={$arWDFile['ID']}&v={$versionHistoryDocInComment}&f={$arWDFile['FILE']['ID']}";
							$arWDFile['THROUGH_VERSION'] = empty($throughVersionComment)? -1 : $throughVersionComment;
						}
						else
						{
							$arWDFile['THROUGH_VERSION'] = (int)$arWDFile['PROPERTY_' . CWebDavIblock::PROPERTY_VERSION . '_VALUE'];
						}


						if (strlen($arWDFile['PATH']) > 0)
						{
							$arWDFile['PATH'] = CHTTP::urnEncode($arWDFile['PATH']);
							$result[$id] = $arWDFile;
						}
					}
				}
			}

			global $CACHE_MANAGER;
			$CACHE_MANAGER->StartTagCache($cachePath);
			foreach ($arIBlockCacheID as $ibID)
				$CACHE_MANAGER->RegisterTag("iblock_id_".$ibID);
			$CACHE_MANAGER->EndTagCache();
			$obCache->EndDataCache(array("RESULT" => $result));
		}
		// not cached

		// check file access rights
		static $op = 'element_read';
		foreach($result as $id => $arWDFile)
		{
			if (!in_array($id, $arValue))
			{
				unset($result[$id]);
				continue;
			}
			else
			{
				if(!isset($arIBlock[$arWDFile['IBLOCK_ID']]))
				{
					continue;
				}
			}

			$arWDIBlock = $arIBlock[$arWDFile['IBLOCK_ID']];
			if ($arWDIBlock['RIGHTS_MODE'] == 'E')
			{
				$ibRights = CWebDavIblock::_get_ib_rights_object('ELEMENT', $id, $arWDIBlock['ID']);
				if (!$ibRights->UserHasRightTo($arWDIBlock['ID'], $id, $op))
				{
					unset($result[$id]);
					continue;
				}
			}
			else
			{
				if (CIBlock::GetPermission($arWDIBlock['ID']) < 'R')
				{
					unset($result[$id]);
					continue;
				}
			}
		}
	}

	//output
	$arResult['FILES'] = $result;
}

/**
 * @param $arWDFile
 * @param $postDatePublish
 * @return array
 */
function WDUFHasHistoryModify($arWDFile, $postDatePublish)
{
	if(!CModule::IncludeModule('bizproc'))
	{
		return false;
	}
	if (CWebDavEditDocBase::isEditable($arWDFile['NAME']))
	{
		$history = new CBPHistoryService();
		$filter  = array(
			'DOCUMENT_ID' => array('webdav', 'CIBlockDocumentWebdavSocnet', $arWDFile['ID']),
		);
		if(stripos($arWDFile['IBLOCK_CODE'], 'shared') !== false)
		{
			$filter  = array(
				'DOCUMENT_ID' => array('webdav', 'CIBlockDocumentWebdav', $arWDFile['ID']),
			);
		}
		if ($postDatePublish)
		{
			$filter['>=MODIFIED'] = $postDatePublish;
		}
		//has history modify by file where date modify > date publish of blog post
		return (bool)$history->GetHistoryList(array(), $filter, array());
	}

	return false;
}

/**
 * @param $arWDFile
 * @return bool
 */
function WDUFGetOnlineEditService($arWDFile)
{
	return CWebDavLogOnlineEdit::getOnlineService(array(
		'IBLOCK_ID' => $arWDFile['IBLOCK_ID'],
		'SECTION_ID' => $arWDFile['IBLOCK_SECTION_ID'],
		'ELEMENT_ID' => $arWDFile['ID'],
	));

}

function WDUFUserFieldEdit(&$arParams, &$arResult)
{
	global $APPLICATION;
	static $userIblockID = false;
	static $groupIblockID = false;
	static $iblockOptionTypes = array("group_files", "shared_files", "user_files");
	static $iblockOptions = array();
	static $arIBlock = array();
	static $DROPPED = null;

	$arResult['ELEMENTS'] = array();
	$arResult['JSON'] = array();
	if (!CModule::IncludeModule('webdav'))
		return false;

	if($DROPPED === null)
	{
		$DROPPED = CWebDavIblock::getDroppedMetaData();
		$DROPPED = $DROPPED['name'];
	}

	//$APPLICATION->AddHeadString('<link href="/bitrix/components/bitrix/webdav/templates/.default/style.css" type="text/css" rel="stylesheet" />'); // for IE style debug
	//	if (in_array($arParams['arUserField']['ENTITY_ID'], $arValidTypes))
	{
		$arResult['controlName'] = $arParams['arUserField']['FIELD_NAME'];

		$arValue = $arParams['arUserField']['VALUE'];

		if (is_array($arValue) && sizeof($arValue) > 0)
		{

			if (empty($iblockOptions))
			{

				foreach($iblockOptionTypes as $type)
				{
					$arOpt = CWebDavIblock::LibOptions($type, false, SITE_ID);

					if (is_set($arOpt, 'id') && (intval($arOpt['id']) > 0))
					{
						$iblockOptions[$type] = $arOpt['id'];
					}
				}

			}

			foreach ($arValue as $elementID)
			{
				$elementID = intval($elementID);
				if ($elementID <= 0)
					continue;

				$title = '';
				$dropped = false;

				$dbElement = CIBlockElement::GetList(
					array(),
					array('ID' => $elementID),
					false,
					false,
					array(
						'ID',
						'NAME',
						'IBLOCK_ID',
						'IBLOCK_SECTION_ID',
						'SOCNET_GROUP_ID',
						'CREATED_BY'
					)
				);

				if (
					$dbElement
					&& $arElement = $dbElement->Fetch()
				)
				{
					$arSectionTree = WDUGetNavChainSections($arElement['IBLOCK_ID'], $arElement['IBLOCK_SECTION_ID'], false);

					$dropped = false;

					if (
						(sizeof($arSectionTree) > 0)
						&& ($arSectionTree[0]['NAME'] == $DROPPED)
					)
					{
						$title = GetMessage('WD_LOCAL_COPY_ONLY');
						$dropped = true;
					}
					else
					{
						$type = array_search($arElement['IBLOCK_ID'], $iblockOptions);

						if ($type == 'group_files')
						{
							if ((sizeof($arSectionTree) > 0))
							{
								$title = $arSectionTree[0]['NAME'];
							}
						}
						elseif ($type == 'user_files')
						{
							if (
								(sizeof($arSectionTree) > 1)
								&& ($arSectionTree[1]['NAME'] == $DROPPED)
							)
							{
								$title = GetMessage('WD_LOCAL_COPY_ONLY');
								$dropped = true;
							}
							elseif (sizeof($arSectionTree) > 0)
							{
								$title = GetMessage('WD_MY_LIBRARY');
								/*$l = sizeof($arSectionTree);
								for($i = 1; $i < $l; $i++)
								{
									$title .= " / " .  $arSectionTree[$i]['NAME'];
								}*/

							}
						}
						else
						{

							if (!isset($arIBlock[$arElement['IBLOCK_ID']]))
							{
								$dbIB = CIBlock::GetList(array(), array('ID' => $arElement['IBLOCK_ID']));
								if ($dbIB && $arIB = $dbIB->Fetch())
								{
									$arIBlock[$arElement['IBLOCK_ID']] = $arIB;
								}
							}

							if (isset($arIBlock[$arElement['IBLOCK_ID']]))
							{
								$title = $arIBlock[$arElement['IBLOCK_ID']]['NAME'];
							}
						}
					}

					$arElement['FILE_SIZE'] = '';

					$dbSize = CIBlockElement::GetProperty($arElement['IBLOCK_ID'], $arElement['ID'], array(), array('CODE' => 'WEBDAV_SIZE'));
					if ($dbSize && $arSize=$dbSize->Fetch())
					{
						$arElement['FILE_SIZE'] = CFile::FormatSize(intval($arSize['VALUE']), 0);
					}

					$arElement['FILE'] = array();

					$dbSize = CIBlockElement::GetProperty($arElement['IBLOCK_ID'], $arElement['ID'], array(), array('CODE' => 'FILE'));
					if ($dbSize && $arSize=$dbSize->Fetch())
					{
						$arElement['FILE'] = CFile::GetFileArray($arSize['VALUE']);
					}

					$arSection =&$arSectionTree[0];
					$detailPath = CWebDavIblock::LibOptions('lib_paths', true, $arElement['IBLOCK_ID']);
					$arPaths = WDUFGetPathOptions($detailPath, $arElement['IBLOCK_ID'], $arSection, $arElement);

					$arElement["URL_VIEW"] = $arPaths["path"];
					$arElement["URL_EDIT"] = $arPaths["edit"];
					$arElement["URL_DELETE_DROPPED"] = $arPaths["delete_dropped"];
					$arElement["URL_GET"] = $arPaths["history_get"];

					$arElement['DROPPED'] = $dropped;
					$arElement['TITLE'] = $title;

					$arResult['ELEMENTS'][] = $arElement;
				}
			}
		}

		// need to load Options for ajax dialogs
		$extDir = WDUFGetExtranetDir();
		if ($extDir !== '')
		{
			$groupIBlockID = CWebDavIblock::LibOptions('group_files', false, SITE_ID);

			if (
				!($groupIBlockID
				&& isset($groupIBlockID['id'])
				&& intval($groupIBlockID['id']) > 0)
			)
			{
				$arGroups = CIBlockWebdavSocnet::GetUserGroups(0, false);

				if (sizeof($arGroups) > 0)
				{
					$arGroup = array_pop($arGroups);
					$groupFilesUrl = str_replace(array("///","//"), "/", "/" . $extDir . '/workgroups/group/'.$arGroup['GROUP_ID'].'/files/');
					$arResult['JSON'][] = $groupFilesUrl;
				}
			}
		}
		else
		{
			$sharedLibID = CWebDavIblock::LibOptions('shared_files', false, SITE_ID);
			if (!($sharedLibID &&
				isset($sharedLibID['id']) &&
				intval($sharedLibID['id']) > 0 &&
				isset($sharedLibID['base_url']) &&
				strlen($sharedLibID['base_url']) > 0
			))
			{
				if (!(
					CModule::IncludeModule('extranet')
					&& (strlen(CExtranet::GetExtranetSiteID()) > 0)
					&& (SITE_ID == CExtranet::GetExtranetSiteID())
				))
				{
					$arResult['JSON'][] = '/docs/';
					$arResult['JSON'][] = '/docs/shared/';
				}
			}

			$userIBlockID = CWebDavIblock::LibOptions('user_files', false, SITE_ID);

			if (
				! (
					$userIBlockID
					&& isset($userIBlockID['id'])
					&& (intval($userIBlockID['id']) > 0)
				)
			)
			{
				$arResult['JSON'][] = '/company/personal/user/' . $GLOBALS['USER']->GetID() . '/files/lib/';
			}

			$groupIBlockID = CWebDavIblock::LibOptions('group_files', false, SITE_ID);

			if (
				! (
					$groupIBlockID
					&& isset($groupIBlockID['id'])
					&& (intval($groupIBlockID['id']) > 0)
				)
			)
			{
				$arGroups = CIBlockWebdavSocnet::GetUserGroups(0, false);
				if (sizeof($arGroups) > 0)
				{
					$arGroup = array_pop($arGroups);
					$arResult['JSON'][] = '/workgroups/group/' . $arGroup['GROUP_ID'] . '/files/';
				}
			}
		}
	}
}

function WDUFGetPathOptions($path, $IBLOCK_ID, $arSection, $arElement = array())
{
	static $arExtranetSite = false;
	static $defSite = false;

	$path = (!empty($path) && is_string($path) ? $path : '');
	$IBLOCK_ID = intval($IBLOCK_ID);
	$SEF_FOLDER = "/";
	$SEF_URL_TEMPLATES = array();

	{ // old version
		$entity = ((strpos($path, "#user_id#") !== false) || (strpos($path, "#USER_ID#") !== false) ? "user" : (
			((strpos($path, "#group_id#") !== false) || (strpos($path, "#GROUP_ID#") !== false)) ? "group" : "lib"));

		if (strpos($path, "#SITE_DIR#") !== false)
			$path = str_replace("#SITE_DIR#", SITE_DIR, $path);
		else if (CModule::IncludeModule('extranet') && (CExtranet::GetExtranetSiteID() == SITE_ID))
		{
			if($arExtranetSite === false)
			{
				$rsSites = CSite::GetByID(SITE_ID);
				$arExtranetSite = $rsSites->Fetch();
				unset($rsSites);
			}
			if ( $arExtranetSite && (strpos($path, $arExtranetSite["DIR"]) === false))
			{
				if($defSite === false)
				{
					$defSite = CSite::GetDefSite();
				}
				if ($entity == "user")
				{
					$intranet_path = COption::GetOptionString("socialnetwork", "user_page", false, $defSite);
					$extranet_path = COption::GetOptionString("socialnetwork", "user_page", false, SITE_ID);
					if (strpos($path, $intranet_path) === 0)
						$path = str_replace($intranet_path, $extranet_path, $path);
				}
				elseif ($entity == "group")
				{
					$intranet_path = COption::GetOptionString("socialnetwork", "workgroups_page", false, $defSite);
					$extranet_path = COption::GetOptionString("socialnetwork", "workgroups_page", false, SITE_ID);
					if (strpos($path, $intranet_path) === 0)
						$path = str_replace($intranet_path, $extranet_path, $path);
				}
				else
					$path = $arExtranetSite["DIR"] . $path;
			}
		}
		$path = str_replace(array("///", "//"), "/", $path);
		if ($entity != "lib")
		{
			$SEF_FOLDER = substr($path, 0, strpos(strtolower($path), ($entity == "user" ? "user/#user_id#/files" : "group/#group_id#/files")));
			$SEF_URL_TEMPLATES = ($entity == "user" ?
				array(
					"path" => "user/#user_id#/files/lib/#path#",
					"view" => "user/#user_id#/files/element/view/#element_id#/",
					"edit" => "user/#user_id#/files/element/edit/#element_id#/#action#/",
					"history" => "user/#user_id#/files/element/history/#element_id#/",
					"history_get" => "user/#user_id#/files/element/historyget/#element_id#/#element_name#"
				) :
				array(
					"path" => "group/#group_id#/files/#path#",
					"view" => "group/#group_id#/files/element/view/#element_id#/",
					"edit" => "group/#group_id#/files/element/edit/#element_id#/#action#/",
					"history" => "group/#group_id#/files/element/history/#element_id#/",
					"history_get" => "group/#group_id#/files/element/historyget/#element_id#/#element_name#"
				)
			);
		}
	}

	if ($entity == "lib" && $path !== '' && $IBLOCK_ID > 0)
	{
		$arUrlRewrite = CUrlRewriter::GetList(array("QUERY" => $path));
		$arRule = array();
		foreach($arUrlRewrite as $arRule)
		{
			if ($arRule["ID"] == "bitrix:webdav")
			{
				$entity = "lib";
				$wdSefPathSettings = COption::GetOptionString('webdav', 'webdav_comp_sef_path_' . $IBLOCK_ID);
				if($wdSefPathSettings && CheckSerializedData($wdSefPathSettings))
				{
					$wdSefPathSettings = @unserialize($wdSefPathSettings);
					$SEF_FOLDER = $wdSefPathSettings['SEF_FOLDER'];
					$wdSefPathSettings = $wdSefPathSettings['SEF_URL_TEMPLATES'];
					if(is_array($wdSefPathSettings))
					{
						$SEF_URL_TEMPLATES = array(
							"path" => $wdSefPathSettings["sections"],
							"view" => $wdSefPathSettings["element"],
							"edit" => $wdSefPathSettings["element_edit"],
							"history" => $wdSefPathSettings["element_history"],
							"history_get" => $wdSefPathSettings["element_history_get"]
						);
						break;
					}
				}
			}

			$arComponents = WDUFGetComponentsOnPage($arRule["PATH"]);
			$entity = false;
			foreach ($arComponents as $arComponent)
			{
				if ($arComponent["COMPONENT_NAME"] == $arRule["ID"])
				{
					$SEF_FOLDER = $arComponent["PARAMS"]["SEF_FOLDER"];
					if (strpos($arRule["ID"], "bitrix:socialnetwork") === 0)
					{
						if ($arRule["ID"] == "bitrix:socialnetwork" &&
							$arComponent["PARAMS"]["FILES_GROUP_IBLOCK_ID"] == $IBLOCK_ID &&
							$arComponent["PARAMS"]["FILES_USER_IBLOCK_ID"] == $IBLOCK_ID)
						{
							$entity = ($arSection["SOCNET_GROUP_ID"] > 0 ? "group" : "user");
						}
						else if ( $arComponent["PARAMS"]["FILES_USER_IBLOCK_ID"] == $IBLOCK_ID &&
							($arRule["ID"] == "bitrix:socialnetwork_user" || $arRule["ID"] == "bitrix:socialnetwork") )
						{
							$entity = "user";
						}
						else if ( $arComponent["PARAMS"]["FILES_GROUP_IBLOCK_ID"] == $IBLOCK_ID &&
							($arRule["ID"] == "bitrix:socialnetwork_group" || $arRule["ID"] == "bitrix:socialnetwork") )
						{
							$entity = "group";
						}
						if (!!$entity)
						{
							$SEF_URL_TEMPLATES = ($entity == "user" ?
								array(
									"path" => $arComponent["PARAMS"]["SEF_URL_TEMPLATES"]["user_files"],
									"view" => "user/#user_id#/files/element/view/#element_id#/",
									"edit" => "user/#user_id#/files/element/edit/#element_id#/#action#/",
									"history" => "user/#user_id#/files/element/history/#element_id#/",
									"history_get" => "user/#user_id#/files/element/historyget/#element_id#/#element_name#"
								) :
								array(
									"path" => $arComponent["PARAMS"]["SEF_URL_TEMPLATES"]["group_files"],
									"view" => "group/#group_id#/files/element/view/#element_id#/",
									"edit" => "group/#group_id#/files/element/edit/#element_id#/#action#/",
									"history" => "group/#group_id#/files/element/history/#element_id#/",
									"history_get" => "group/#group_id#/files/element/historyget/#element_id#/#element_name#"
								)
							);
						}
					}
					else if ($arRule["ID"] == "bitrix:webdav" && $arComponent["PARAMS"]["IBLOCK_ID"] == $IBLOCK_ID)
					{
						$entity = "lib";
						$SEF_URL_TEMPLATES = array(
							"path" => $arComponent["PARAMS"]["SEF_URL_TEMPLATES"]["sections"],
							"view" => $arComponent["PARAMS"]["SEF_URL_TEMPLATES"]["element"],
							"edit" => $arComponent["PARAMS"]["SEF_URL_TEMPLATES"]["element_edit"],
							"history" => $arComponent["PARAMS"]["SEF_URL_TEMPLATES"]["element_history"],
							"history_get" => $arComponent["PARAMS"]["SEF_URL_TEMPLATES"]["element_history_get"]
						);
					}
					if (!!$entity)
					{
						$SEF_URL_TEMPLATES["component"] = $arRule["ID"];
						break 2;
					}
				}
			}
		}
		$entity = (!$entity ? $SEF_URL_TEMPLATES["entity"] : $entity);
	}
	$repl = array("#id#", "#ELEMENT_ID#", "#element_id#", "#name#", "#ELEMENT_NAME#", "#element_name#", "#action#", "//");
	$patt = array("#ID#", "#ID#", "#ID#", "#NAME#", "#NAME#", "#NAME#", "#ACTION#", "/");
	if ($entity != "lib" && !empty($arSection))
	{
		$repl = array_merge(
			array(
				"#SOCNET_USER_ID#", "#USER_ID#", "#SOCNET_GROUP_ID#", "#GROUP_ID#", "#SOCNET_OBJECT#", "#SOCNET_OBJECT_ID#",
				"#socnet_user_id#", "#user_id#", "#socnet_group_id#", "#group_id#", "#socnet_object#", "#socnet_object_id#"),
			$repl);
		$patt = array_merge(
			array(
				$arSection["CREATED_BY"], $arSection["CREATED_BY"], $arSection["SOCNET_GROUP_ID"], $arSection["SOCNET_GROUP_ID"],
				$entity,
				($arSection["SOCNET_GROUP_ID"] > 0 ? $arSection["SOCNET_GROUP_ID"] : $arSection["CREATED_BY"]),
				$arSection["CREATED_BY"], $arSection["CREATED_BY"], $arSection["SOCNET_GROUP_ID"], $arSection["SOCNET_GROUP_ID"],
				$entity,
				($arSection["SOCNET_GROUP_ID"] > 0 ? $arSection["SOCNET_GROUP_ID"] : $arSection["CREATED_BY"])),
			$patt);
	}
	if (!empty($arElement))
	{
		$repl[] = "#ID#"; $patt[] = $arElement["ID"];
		$repl[] = "#NAME#"; $patt[] = $arElement["NAME"];
	}

	foreach($SEF_URL_TEMPLATES as $key => $val)
		$SEF_URL_TEMPLATES[$key] = str_replace($repl, $patt, $SEF_FOLDER ."/". $val);

	$SEF_URL_TEMPLATES["path"] = str_replace(array("#path#", "#PATH#"), "", $SEF_URL_TEMPLATES["path"]);
	$SEF_URL_TEMPLATES["delete_dropped"] = str_replace("#ACTION#", "delete_dropped", $SEF_URL_TEMPLATES["edit"]);
	$SEF_URL_TEMPLATES["edit"] = str_replace("#ACTION#", "edit", $SEF_URL_TEMPLATES["edit"]);
	$SEF_URL_TEMPLATES["entity"] = $entity;

	return $SEF_URL_TEMPLATES;
}

function WDUFGetComponentsOnPage($filesrc = false)
{
	static $cache = array();
	if (!array_key_exists($filesrc, $cache))
	{
		$text = ''; $arResult = array();
		if ($filesrc !== false)
		{
			$io = CBXVirtualIo::GetInstance();
			$filesrc = $io->CombinePath("/", $filesrc);
			$filesrc = CSite::GetSiteDocRoot(SITE_ID).$filesrc;
			$f = $io->GetFile($filesrc);
			$text = $f->GetContents();
		}
		if ($text != '')
		{
			$arPHP = PHPParser::ParseFile($text);

			foreach ($arPHP as $php)
			{
				$src = $php[2];
				if (stripos($src, '$APPLICATION->IncludeComponent(') !== false)
					$arResult[] = PHPParser::CheckForComponent2($src);
			}
		}
		$cache[$filesrc] = $arResult;
	}
	return $cache[$filesrc];
}

function WDUFGetServiceEditDoc()
{
	return CWebDavTools::getServiceEditDocForCurrentUser();
}
?>