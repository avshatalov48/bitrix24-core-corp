<?php
IncludeModuleLangFile(__FILE__);

if (!CModule::IncludeModule("iblock"))
	return;
elseif (!CModule::IncludeModule("socialnetwork"))
	return;

class CIBlockWebdavSocnet
{
	protected static $lastGroupIdAddedOnHit = null;
	protected static $lastGroupOwnerIdAddedOnHit = null;
	protected static $storedFileIdsByComment = array();

	static $ops = array(
		'view' => 'R',
		'write_limited' => 'E',
		'bizproc' => 'U',
		'write' => 'X',
	);

	const XML_ID_CREATED_DOC_FOLDER = 'CREATED_DOC_FOLDER';
	const XML_ID_DOWNLOADED_FOLDER = 'DOWNLOADED_FOLDER';

	public static function ClearTagCache($ID)
	{
		global $CACHE_MANAGER;
		$CACHE_MANAGER->ClearByTag($ID);
	}

	/*
	RegisterModuleDependences('socialnetwork', 'OnSocNetFeaturesAdd', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetFeaturesAdd');
	RegisterModuleDependences('socialnetwork', 'OnSocNetFeaturesUpdate', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetFeaturesUpdate');
	RegisterModuleDependences('socialnetwork', 'OnSocNetFeatures', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetFeatures');
	RegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupAdd', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetUserToGroupAdd');
	RegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupUpdate', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetUserToGroupUpdate');
	RegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupDelete', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetUserToGroupDelete');
	RegisterModuleDependences('socialnetwork', 'OnSocNetGroupDelete', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetGroupDelete');
	RegisterModuleDependences('socialnetwork', 'OnSocNetGroupAdd', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetGroupAdd');
	RegisterModuleDependences('socialnetwork', 'OnSocNetGroupUpdate', 'webdav', 'CIBlockWebdavSocnet', 'OnSocNetGroupUpdate');
	RegisterModuleDependences('socialnetwork', 'OnAfterSocNetLogCommentAdd', 'webdav', 'CIBlockWebdavSocnet', 'CopyCommentRights');
	*/


	public static function storeFileIdsByComment($id, array $fileIds)
	{
		self::$storedFileIdsByComment[$id] = $fileIds;
	}

	function CopyCommentRights($ID, $arFields)
	{
		if(empty($arFields['LOG_ID']))
		{
			return false;
		}
		if(empty(self::$storedFileIdsByComment[$arFields['SOURCE_ID']]))
		{
			return false;
		}

		$arRights0 = array();
		$rConst = 0;
		$rs = CTask::GetList(
			array("LETTER"=>"asc"),
			array(
				"MODULE_ID" => "iblock",
				"LETTER" => "R",
			)
		);
		if($ar = $rs->Fetch())
		{
			$rConst = $ar["ID"];
		}
		else
		{
			return false;
		}

		$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $arFields["LOG_ID"]));
		$i = 1;
		while($arRight = $dbRight->Fetch())
		{
			/*$arRights0["n" .$i] = Array(
				"GROUP_CODE" => $arRight["GROUP_CODE"],
				"TASK_ID" => $rConst,
			);*/
			$gc = $arRight["GROUP_CODE"];
			if(array_key_exists($gc, $arRights0) && $arRights0[$gc]["TASK_ID"] >= $rConst)
			{
				continue;
			}
			$arRights0[$gc] = Array(
				"KEY" => "n" .$i,
				"GROUP_CODE" => $gc,
				"TASK_ID" => $rConst,
			);
			$i++;
		}

		$arFilesID = self::$storedFileIdsByComment[$arFields['SOURCE_ID']];
		$arFiles = array();
		foreach($arFilesID as $id)
		{
			$id = intval($id);
			if (intval($id) > 0)
				$arFiles[] = $id;
		}
		if(!$arFiles)
		{
			return;
		}

		$ibe = new CIBlockElement();
		$dbWDFile = $ibe->GetList(array(), array('ID' => $arFiles, 'SHOW_NEW' => 'Y'), false, false, array('ID', 'NAME', 'SECTION_ID', 'IBLOCK_ID', 'WF_NEW'));
		while($dbWDFile && $arWDFile = $dbWDFile->Fetch())
		{
			$arRights1 = $arRights0;
			$ob = new CIBlockElementRights($arWDFile['IBLOCK_ID'], $arWDFile['ID']);
			$ar = $ob->GetRights();
			foreach($ar as $k=>$v)
			{
				$gc = $v["GROUP_CODE"];
				if(array_key_exists($gc, $arRights1) && $arRights1[$gc]["TASK_ID"] >= $v["TASK_ID"])
				{
					continue;
				}
				$arRights1[$gc] = Array(
					"KEY" => $k,
					"GROUP_CODE" => $gc,
					"TASK_ID" => $v["TASK_ID"],
				);
			}
			$arRights2 = array();
			foreach($arRights1 as $v)
			{
				$arRights2[$v["KEY"]] = Array(
					"GROUP_CODE" => $v["GROUP_CODE"],
					"TASK_ID" => $v["TASK_ID"]
				);
			}
			$ob->SetRights($arRights2);
		}
	}

	public static function OnSocNetFeaturesAdd($id, $fields)
	{
		static $addGroupFilesFeatures = false;

		if(!$addGroupFilesFeatures && isset($fields['ACTIVE']) && $fields['ACTIVE'] == 'Y')
		{
			if($fields
				&& isset($fields['FEATURE'])
				&& $fields['FEATURE'] == 'files'
				&& $fields['ENTITY_TYPE'] == 'G'
				&& $fields['ENTITY_ID']
			)
			{
				$addGroupFilesFeatures = true;
				$groupId = $fields['ENTITY_ID'];

				//add section to files in this group!
				$groupSectionCreateSuccess = (bool)self::groupSectionCreate($groupId);
				if(!empty($groupId) && $groupSectionCreateSuccess && self::$lastGroupIdAddedOnHit == $groupId && self::$lastGroupOwnerIdAddedOnHit)
				{
					$dispatcher = new \Bitrix\Webdav\InviteDispatcher;
					$attachObjectType = CWebDavSymlinkHelper::ENTITY_TYPE_GROUP;
					$attachObjectId = (int)$groupId;

					$inviteComponentParams = array(
						'attachObject' => array(
							'id' => $attachObjectId,
							'type' => $attachObjectType,
						),
						'attachToUserId' => self::$lastGroupOwnerIdAddedOnHit,
						'inviteFromUserId' => self::$lastGroupOwnerIdAddedOnHit,
						'canEdit' => true,
					);
					$response =
						CWebDavTools::allowAutoconnectShareGroupFolder()?
							$dispatcher->processActionConnect($inviteComponentParams) :
							$dispatcher->processActionInvite($inviteComponentParams);
					if($response['status'] == $dispatcher::STATUS_SUCCESS)
					{

					}
				}
			}
		}
		CIBlockWebdavSocnet::ClearTagCache('wd_socnet');
	}

	public static function OnSocNetFeaturesUpdate($id, $fields)
	{
		static $updateGroupFilesFeatures = false;

		if(!$updateGroupFilesFeatures && isset($fields['ACTIVE']) && $fields['ACTIVE'] == 'N')
		{
			$features = CSocNetFeatures::getById($id);
			if($features
				&& isset($features['FEATURE'])
				&& $features['FEATURE'] == 'files'
				&& $features['ENTITY_TYPE'] == 'G'
				&& $features['ENTITY_ID']
			)
			{
				$updateGroupFilesFeatures = true;
				$groupId = $features['ENTITY_ID'];

				if(!empty($groupId))
				{
					$dispatcher = new \Bitrix\Webdav\InviteDispatcher;
					$attachObjectType = CWebDavSymlinkHelper::ENTITY_TYPE_GROUP;
					$attachObjectId = (int)$groupId;

					$inviteComponentParams = array(
						'attachObject' => array(
							'id' => $attachObjectId,
							'type' => $attachObjectType,
						),
						'unshareUserIds' => array(), //all users
					);
					$response = $dispatcher->processActionUnshare($inviteComponentParams);
					if($response['status'] == $dispatcher::STATUS_SUCCESS)
					{

					}
				}
			}
		}
		elseif(isset($fields['ACTIVE']) && $fields['ACTIVE'] == 'Y')
		{
			$features = CSocNetFeatures::getById($id);
			if($features
				&& isset($features['FEATURE'])
				&& $features['FEATURE'] == 'files'
				&& $features['ENTITY_TYPE'] == 'G'
				&& $features['ENTITY_ID']
			)
			{
				$groupId = $features['ENTITY_ID'];
				//add section to files in this group!
				$groupSectionCreateSuccess = (bool)self::groupSectionCreate($groupId);
			}
		}
		CIBlockWebdavSocnet::ClearTagCache('wd_socnet');
	}

	public static function OnBeforeSocNetFeatures($ID) //  === OnBeforeSocNetFeaturesDelete !!!
	{
		CIBlockWebdavSocnet::ClearTagCache('wd_socnet');
	}

	public static function OnSocNetUserToGroupAdd($ID, $arFields)
	{
		if (intval($ID) <= 0)
		{
			return false;
		}

		if (!CModule::IncludeModule('socialnetwork'))
		{
			return false;
		}

		if (!CModule::IncludeModule('webdav'))
		{
			return false;
		}
		if(
			isset($arFields['ROLE']) && isset($arFields['USER_ID']) &&
			(
				$arFields['ROLE'] == SONET_ROLES_USER ||
				$arFields['ROLE'] == SONET_ROLES_MODERATOR ||
				$arFields['ROLE'] == SONET_ROLES_OWNER
			)

		)
		{
			if(!(isset($arFields['GROUP_ID'])))
			{
				$query = CSocNetUserToGroup::GetList(array(), array('ID' => $ID), false, false, array('GROUP_ID'));
				if($query)
				{
					$row = $query->fetch();
					if($row)
					{
						$groupId = $row['GROUP_ID'];
					}
				}
			}
			else
			{
				$groupId = $arFields['GROUP_ID'];
			}
			if(!empty($groupId) && CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $groupId, 'files'))
			{
				$dispatcher = new \Bitrix\Webdav\InviteDispatcher;
				$attachObjectType = CWebDavSymlinkHelper::ENTITY_TYPE_GROUP;
				$attachObjectId = (int)$groupId;

				$inviteComponentParams = array(
					'attachObject' => array(
						'id' => $attachObjectId,
						'type' => $attachObjectType,
					),
					'attachToUserId' => $arFields['USER_ID'],
					'inviteFromUserId' => $arFields['USER_ID'],
					'canEdit' => true,
				);
				$response =
					CWebDavTools::allowAutoconnectShareGroupFolder()?
						$dispatcher->processActionConnect($inviteComponentParams) :
						$dispatcher->processActionInvite($inviteComponentParams);
				if($response['status'] == $dispatcher::STATUS_SUCCESS)
				{

				}
			}
		}


		CIBlockWebdavSocnet::ClearTagCache('wd_socnet');
	}

	public static function OnSocNetUserToGroupUpdate($ID, $arFields)
	{
		if (intval($ID) <= 0)
		{
			return false;
		}

		if (!CModule::IncludeModule('socialnetwork'))
		{
			return false;
		}

		if (!CModule::IncludeModule('webdav'))
		{
			return false;
		}
		if(
			isset($arFields['ROLE']) &&
			(
				$arFields['ROLE'] == SONET_ROLES_USER ||
				$arFields['ROLE'] == SONET_ROLES_MODERATOR ||
				$arFields['ROLE'] == SONET_ROLES_OWNER
			)

		)
		{
			if(!(isset($arFields['USER_ID'])))
			{
				$query = CSocNetUserToGroup::GetList(array(), array('ID' => $ID), false, false, array('USER_ID', 'GROUP_ID'));
				if($query)
				{
					$row = $query->fetch();
					if($row)
					{
						$userId = $row['USER_ID'];
						$groupId = $row['GROUP_ID'];
					}
				}
			}
			if(!empty($userId) && !empty($groupId) && CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $groupId, 'files'))
			{
				$dispatcher = new \Bitrix\Webdav\InviteDispatcher;
				$attachObjectType = CWebDavSymlinkHelper::ENTITY_TYPE_GROUP;
				$attachObjectId = (int)$groupId;

				$inviteComponentParams = array(
					'attachObject' => array(
						'id' => $attachObjectId,
						'type' => $attachObjectType,
					),
					'attachToUserId' => $userId,
					'inviteFromUserId' => $userId,
					'canEdit' => true,
				);
				$response =
					CWebDavTools::allowAutoconnectShareGroupFolder()?
						$dispatcher->processActionConnect($inviteComponentParams) :
						$dispatcher->processActionInvite($inviteComponentParams);

				if($response['status'] == $dispatcher::STATUS_SUCCESS)
				{

				}
			}
		}

		CIBlockWebdavSocnet::ClearTagCache('wd_socnet');
	}

	public static function OnSocNetUserToGroupDelete($ID, $arFields)
	{
		if (intval($ID) <= 0)
		{
			return false;
		}

		if (!CModule::IncludeModule('socialnetwork'))
		{
			return false;
		}

		if (!CModule::IncludeModule('webdav'))
		{
			return false;
		}
		if(
			isset($arFields['ROLE']) &&
			(
				$arFields['ROLE'] == SONET_ROLES_USER ||
				$arFields['ROLE'] == SONET_ROLES_MODERATOR ||
				$arFields['ROLE'] == SONET_ROLES_OWNER
			)

		)
		{
			$userId = $arFields['USER_ID'];
			$groupId = $arFields['GROUP_ID'];

			if(!empty($userId) && !empty($groupId))
			{
				$dispatcher = new \Bitrix\Webdav\InviteDispatcher;
				$attachObjectType = CWebDavSymlinkHelper::ENTITY_TYPE_GROUP;
				$attachObjectId = (int)$groupId;

				$inviteComponentParams = array(
					'attachObject' => array(
						'id' => $attachObjectId,
						'type' => $attachObjectType,
					),
					'attachToUserId' => $userId,
					'inviteFromUserId' => $userId,
					'canEdit' => true,
				);
				$response = $dispatcher->processActionDisconnect($inviteComponentParams);
				if($response['status'] == $dispatcher::STATUS_SUCCESS)
				{

				}
			}
		}

		CIBlockWebdavSocnet::ClearTagCache('wd_socnet');
	}

	public static function OnSocNetGroupAdd($id, &$arFields) // calls OnSocNetUserToGroupAdd
	{
		self::$lastGroupIdAddedOnHit = $id;
		self::$lastGroupOwnerIdAddedOnHit = !empty($arFields['OWNER_ID'])? $arFields['OWNER_ID'] : false;
		CIBlockWebdavSocnet::ClearTagCache('wd_socnet');
	}

	public static function OnSocNetGroupUpdate($ID, $arFields)
	{
		CIBlockWebdavSocnet::ClearTagCache('wd_socnet');
	}

	public static function OnSocNetGroupDelete($groupId)
	{
		$iblockIds = self::GetGroupIblock();

		foreach ($iblockIds as $iblockId)
		{
			$result = CIBlockWebdavSocnet::GetSectionID($iblockId, 'group', $groupId);
			if (intval($result) > 0)
			{
				if(!empty($groupId))
				{
					$dispatcher = new \Bitrix\Webdav\InviteDispatcher;
					$attachObjectType = CWebDavSymlinkHelper::ENTITY_TYPE_GROUP;
					$attachObjectId = (int)$groupId;

					$inviteComponentParams = array(
						'attachObject' => array(
							'id' => $attachObjectId,
							'type' => $attachObjectType,
						),
						'unshareUserIds' => array(), //all users
					);
					$response = $dispatcher->processActionUnshare($inviteComponentParams);
					if($response['status'] == $dispatcher::STATUS_SUCCESS)
					{

					}
				}

				CIBlockSection::Delete($result);
			}
		}

		CIBlockWebdavSocnet::ClearTagCache('wd_socnet');
	}

	public static function GetGroupIblock()
	{
		$result = array();

		if (! CModule::IncludeModule('iblock'))
			return $result;

		$rsIBlock = CIBlock::GetList(array(), array("ACTIVE" => "Y", "CHECK_PERMISSIONS"=>"N", "CODE"=>"group_files%"));
		while($arIBlock = $rsIBlock->Fetch())
			$result[] = $arIBlock["ID"];

		return $result;
	}

	static function GetUsers($iblockID)
	{
		$userTree = array();

		$arFilter = array(
			"IBLOCK_ID" => $iblockID,
			"SOCNET_GROUP_ID" => false,
			"SECTION_ID" => 0,
			"CHECK_PERMISSIONS" => "N"
		);
		$dbSection = CIBlockSection::GetList(array(), $arFilter);
		while ($arSection = $dbSection->Fetch())
		{
			$userID = $arSection['CREATED_BY'];
			$dbUser = CUser::GetByID($userID);
			if ($arUser = $dbUser->Fetch())
			{
				$userTree[$userID] = $arUser;
				$userTree[$userID]['SECTION'] = $arSection['ID'];
			}
		}

		return $userTree;
	}

	static function GetUserGroups($userID = 0, $bGetFolders = true)
	{
		static $oCache = null;
		static $CACHE_PATH = "/webdav/sonet_user_groups/";
		if (! CBXFeatures::IsFeatureEnabled("Workgroups"))
			return array();

		$userID = intval($userID);
		if (intval($userID) <= 0)
			$userID = $GLOBALS['USER']->GetID();

		//$currentUserGroups = CWebDavBase::CustomDataCache($CACHE_PATH, $userID);
		//if (!$currentUserGroups)
		//{
			$currentUserGroups = array();
			$db_res = CSocNetUserToGroup::GetList(
				array("GROUP_NAME" => "ASC"),
				array("USER_ID" => $userID),
				false,
				false,
				array("GROUP_ID", "GROUP_NAME", "GROUP_ACTIVE", "GROUP_CLOSED", "ROLE")
			);
			while ($res = $db_res->GetNext())
			{
				if (
					($res['GROUP_ACTIVE'] == 'Y') &&
					($res['GROUP_CLOSED'] == 'N') &&
					($res['ROLE'] != SONET_ROLES_BAN) &&
					($res['ROLE'] != SONET_ROLES_REQUEST))
				{
					$currentUserGroups[$res["GROUP_ID"]] = $res;
				}
			}

			$arGroupID = array_keys($currentUserGroups);
			if (is_array($arGroupID) && (sizeof($arGroupID) > 0))
			{
				$arFeatures = CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $arGroupID, 'files');
				foreach ($arFeatures as $groupID => $enabled)
					if (!$enabled)
						unset($currentUserGroups[$groupID]);
			}

			if ($bGetFolders)
			{
				$groupIBlock = CWebDavIblock::LibOptions('group_files', false, SITE_ID);
				if ($groupIBlock && isset($groupIBlock['id']) && intval($groupIBlock['id']) > 0)
				{
					$arFilter = array(
						"IBLOCK_ID" => intval($groupIBlock['id']),
						"SECTION_ID" => 0,
						"CHECK_PERMISSIONS" => "N"
					);
					$dbSection = CIBlockSection::GetList(array(), $arFilter, false, array('ID', 'SOCNET_GROUP_ID'));
					while ($arGroupSection = $dbSection->Fetch())
					{
						if (isset($currentUserGroups[$arGroupSection['SOCNET_GROUP_ID']]))
							$currentUserGroups[$arGroupSection['SOCNET_GROUP_ID']]['SECTION_ID'] = $arGroupSection['ID'];
					}

					//CWebDavBase::CustomDataCache($CACHE_PATH, $userID, $currentUserGroups, 'wd_socnet, iblock_id_'.intval($groupIBlock['id'])); // do not save to cache if LibOptions('group_files') is empty
				}
			}
		//}
		return $currentUserGroups;
	}

	static function GetGroups($iblockID)
	{
		$groupTree = array();

		$arFilter = array(
			"IBLOCK_ID" => $iblockID,
			"SECTION_ID" => 0,
			"CHECK_PERMISSIONS" => "N"
		);
		$dbSection = CIBlockSection::GetList(array(), $arFilter, false, array('ID', 'SOCNET_GROUP_ID'));
		while ($arGroupSection = $dbSection->Fetch())
		{
			$groupID = $arGroupSection['SOCNET_GROUP_ID'];
			$section = $arGroupSection['ID'];
			$group = CSocNetGroup::GetByID($groupID);
			if ($group)
			{
				$group['SECTION'] = $section;
				$groupTree[$groupID] = $group;
			}
		}

		return $groupTree;
	}

	static function GetSectionID($iblockID, $entity_type, $entity_id)
	{
		$result = false;
		if (CModule::IncludeModule('iblock'))
		{
			$arFilter = array(
				"IBLOCK_ID" => $iblockID,
				"SOCNET_GROUP_ID" => false,
				"CHECK_PERMISSIONS" => "N",
				"SECTION_ID" => 0);
			if ($entity_type == "user")
				$arFilter["CREATED_BY"] = $entity_id;
			else
				$arFilter["SOCNET_GROUP_ID"] = $entity_id;

			$db_res = CIBlockSection::GetList(array(), $arFilter);
			if ($db_res && $res = $db_res->Fetch())
			{
				$result = $res["ID"];
			}
		}
		return $result;
	}

	static function CanAccessFiles($iblock_id, $entity_type, $entity_id)
	{
		$result = false;
		$iblock_id = intval($iblock_id);
		$entity_id = intval($entity_id);

		if (
			($iblock_id > 0) &&
			($entity_id > 0) &&
			($entity_type == 'group' || $entity_type == 'user')
		)
		{
			//cache
			$value = false;
			static $data = array();
			$CACHE_PATH = "/".SITE_ID."/webdav/can_access_files";
			$CACHE_ID = $iblock_id;
			$CACHE_TIME = 3600*24*30;
			$docCache = new CPHPCache;

			if (!isset($data[$iblock_id]))
			{
				if ($docCache->InitCache($CACHE_TIME, $CACHE_ID, $CACHE_PATH))
					$value = $docCache->GetVars();
				$data[$iblock_id] = $value;
			}

			if (isset($data[$iblock_id][$entity_type][$entity_id]))
				return $data[$iblock_id][$entity_type][$entity_id];
			//end cache

			CModule::IncludeModule('iblock');
			$rIB = CIBlock::GetList(
				array(),
				array(
					'ID' => $iblock_id,
					"CHECK_PERMISSIONS"=>"N"
				)
			);
			if (
				$rIB
				&& ($arIB = $rIB->Fetch())
				&& ($arIB["RIGHTS_MODE"] === "E")
			)
			{
				$rootSectionID = self::GetSectionID($iblock_id, $entity_type, $entity_id);
				if ($rootSectionID !== false)
				{
					$ibRights = new CIBlockSectionRights($iblock_id, $rootSectionID);
					$result = $ibRights->UserHasRightTo($iblock_id, $rootSectionID, 'section_read');

					if (!$result)
					{
						$arParams = array(
							"DOCUMENT_TYPE" => array(
								"webdav",
								"CIBlockDocumentWebdavSocnet",
								implode("_", array("iblock", $iblock_id, $entity_type, $entity_id))
							),
							"ROOT_SECTION_ID" => $rootSectionID,
							"ATTRIBUTES" => (
								($entity_type == "user")
									? array('user_id' => $entity_id)
									: array('group_id' => $entity_id)
							)
						);
						$ob = new CWebDavIblock($iblock_id, '', $arParams);
						if ($ob && empty($ob->arError) && ($ob->permission > 'D'))
						{
							$files = array();
							$options = array("path" => '/', "depth" => 1);
							$res = $ob->PROPFIND($options, $files, array("return" => "array"));
							$result = (is_array($res) && (sizeof($res['RESULT']) > 0)); // at least 1 item can be read
						}
					}
				}
				else // no files exist, no rights set, but feature is turn on in group
				{
					return true;
				}
			}
			else
			{
				$result = (CIBlock::GetPermission($iblock_id) > "D");
			}

			// cache
			if ($data[$iblock_id] === false)
				$data[$iblock_id] = array();

			$data[$iblock_id][$entity_type][$entity_id] = $result;

			$docCache->Clean($CACHE_ID, $CACHE_PATH);
			$docCache->InitCache($CACHE_TIME, $CACHE_ID, $CACHE_PATH);
			if ($docCache->StartDataCache())
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->StartTagCache($CACHE_PATH);
				$CACHE_MANAGER->RegisterTag("iblock_id_".$iblock_id);
				$CACHE_MANAGER->RegisterTag('wd_socnet');
				$CACHE_MANAGER->EndTagCache();
				$docCache->EndDataCache($data[$iblock_id]);
			}
			// end cache
		}
		return $result;
	}


	static function UserERights($iblockID)
	{
		if (CIBlock::GetArrayByID($iblockID, "RIGHTS_MODE") === "E")
		{
			return;
		}

		$arUsers = self::GetUsers($iblockID);

		foreach ($arUsers as $userID => $user)
		{
			foreach (self::$ops as $op => $opTrans)
				$arUsers[$userID]["Operations"][$op] = CSocNetFeaturesPerms::GetOperationPerm(SONET_ENTITY_USER, $userID, 'files', $op);
		}
		$arTasks = CWebDavIblock::GetTasks();
		// set e rights
		$arFields = array(
			'RIGHTS_MODE' => 'E',
			'GROUP_ID' => array()
		);

		$ib = new CIBlock();
		$res = $ib->Update($iblockID, $arFields);

		$ibr = new CIBlockRights($iblockID);
		$rights = array();
		$rights['n0'] = array('GROUP_CODE' => 'G1', 'DO_CLEAN' => 'Y', 'TASK_ID' => $arTasks['X']); // admins
		$rights['n1'] = array('GROUP_CODE' => 'G2', 'DO_CLEAN' => 'Y', 'TASK_ID' => $arTasks['D']); // nobody
		$ibr->SetRights($rights);
		foreach ($arUsers as $userID => $user)
		{
			$sectionID = intval($user['SECTION']);

			$ibrs = new CIBlockSectionRights($iblockID, $sectionID);
			$arRights = array();
			$arRights['n0'] = array('GROUP_CODE' => "U".$userID, 'TASK_ID' => $arTasks['X']); // owner	- full access, nobody  - denied (inherited)
			//$ibrs->SetRights($rights);

			// get old permissions
			$rights = array();
			foreach($user["Operations"] as $op => $subj)
			{
				if ($subj)
				{
					if ($subj == 'A') $subj = 'G2';
					elseif ($subj == 'C') $subj = 'AU';
					elseif ($subj == 'Z') continue; // already set

					$rights[$subj] = $arTasks[self::$ops[$op]];
					if (self::$ops[$op] == 'E')
						$rights['CR'] = $arTasks['W'];
				}
			}
			$i = 1;
			foreach($rights as $subj => $task)
			{
				$arRights['n'.$i++] = array(
					'GROUP_CODE' => $subj,
					'TASK_ID' => $task,
					'DO_CLEAN' => 'NOT'
				);
			}

			// apply to exist files
			$arFilter = array(
				"IBLOCK_ID" => $iblockID,
				"ID" => $sectionID,
				//"SECTION_ID" => $sectionID,
				"CHECK_PERMISSIONS" => "N"
			);
			$dbSection = CIBlockSection::GetList(array(), $arFilter, false, array('ID'));
			while ($arSection = $dbSection->Fetch())
			{
				$ibrs = new CIBlockSectionRights($iblockID, $arSection['ID']);
				$ibrs->SetRights($arRights);
				self::CreateSharedFolder($iblockID, $sectionID, $userID, true);
			}

			//$dbElements = CIBlockElement::GetList(array(), $arFilter, false, false, array('ID'));
			//while ($arElement = $dbElements->Fetch())
			//{
				//$ibre = new CIBlockElementRights($iblockID, $arElement['ID']);
				//$ibre->SetRights($arRights);
			//}
		}
	}

	public static function CreateSharedFolder($iblockID, $sectionID, $userID, $setRights = false)
	{
		$_sharedGroup = 'AU';
		$_sharedTask = 'R';
		$_shareName = GetMessage("WD_SHARED_FILES");
		$_shareXMLID = 'SHARED_FOLDER';

		$arTasks = CWebDavIblock::GetTasks();
		$arFilter = array(
			"IBLOCK_ID" => $iblockID,
			"SECTION_ID" => $sectionID,
			"CHECK_PERMISSIONS" => "N"
		);
		$_shareRights = array("n0" => array("GROUP_CODE" => $_sharedGroup, "TASK_ID" => $arTasks[$_sharedTask]));

		$arShare = null;
		$se = new CIBlockSection();
		$arFilter['NAME'] = $_shareName;
		$dbShare = CIBlockSection::GetList(array(), $arFilter, false, array('ID', 'CREATED_BY', 'MODIFIED_BY', 'XML_ID'));
		if ($dbShare && ($arShare1 = $dbShare->Fetch()))
		{
			$arShare =& $arShare1;
		}
		else
		{
			unset($arFilter['NAME']); // search for renamed shared folder
			$arFilter['XML_ID'] = $_shareXMLID;
			$dbShare = CIBlockSection::GetList(array(), $arFilter, false, array('ID', 'CREATED_BY', 'XML_ID'));
			if ($dbShare && ($arShare2 = $dbShare->Fetch()))
			{
				$arShare =& $arShare2;
			}
		}
		if ($arShare)
		{
			if ($setRights)
			{
				$sRight      = new CIBlockSectionRights($iblockID, $arShare['ID']);
				$arRights    = $sRight->GetRights($arShare['ID']);
				$validRights = false;
				foreach ($arRights as $right)
				{
					if (($right['GROUP_CODE'] == $_sharedGroup) && ($right['TASK_ID'] == $arTasks[$_sharedTask]))
					{
						$validRights = true;
						break;
					}
				}
				if (!$validRights)
				{
					$sRight->SetRights($_shareRights);
				}
			}
			$recoverData = array();
			if ($arShare['XML_ID'] != $_shareXMLID)
			{
				$recoverData['XML_ID'] = $_shareXMLID;
			}
			if ($arShare['CREATED_BY'] != $userID)
			{
				$recoverData['CREATED_BY'] = $userID;
			}
			if ($arShare['MODIFIED_BY'] != $userID)
			{
				$recoverData['MODIFIED_BY'] = $userID;
			}

			if(!empty($recoverData))
			{
				$se->Update($arShare['ID'], $recoverData);
			}
		}
		else
		{
			$arFilter['NAME'] = $_shareName;
			$arFilter["RIGHTS"] = $_shareRights;
			$arFilter['IBLOCK_SECTION_ID'] = $arFilter['SECTION_ID'];
			$arFilter["CREATED_BY"] = $userID;
			$arFilter["MODIFIED_BY"] = $userID;
			$arFilter['XML_ID'] = $_shareXMLID;
			$newSecID = $se->Add($arFilter);

			$obSectionRights = new CIBlockSectionRights($iblockID, $newSecID);
			$obSectionRights->SetRights($arFilter["RIGHTS"]);
		}
	}

	public static function getNameDownloadedFolder()
	{
		return GetMessage('WD_DOWNLOADED_FILES');
	}

	public static function createDownloadedFolder($iblockId, $sectionId, $userId)
	{
		$droppedMetaData = CWebDavIblock::getDroppedMetaData();
		$name = self::getNameDownloadedFolder();
		if(isset($droppedMetaData['alias']))
		{
			$name = $droppedMetaData['alias'];
		}

		return self::createFolder($name, null, $iblockId, $sectionId, $userId);
	}

	public static function createSavedFolder($iblockId, $sectionId, $userId)
	{
		$savedMetaData = CWebDavIblock::getSavedMetaData();
		return self::createFolder($savedMetaData['alias'], null, $iblockId, $sectionId, $userId);
	}

	public static function getNameCreatedDocFolder()
	{
		return GetMessage('WD_CREATED_DOC_FILES');
	}

	public static function isCreatedDocFolder($iblockId, $sectionId, $userId)
	{
		return self::isSpecialUserFolder($iblockId, $sectionId, self::XML_ID_CREATED_DOC_FOLDER, $userId);
	}

	protected static function isSpecialUserFolder($iblockId, $sectionId, $xmlId, $userId)
	{
		$filter = array(
			'IBLOCK_ID' => $iblockId,
			'ID' => $sectionId,
			'CHECK_PERMISSIONS' => 'N',
			'XML_ID' => $xmlId,
			'CREATED_BY' => $userId,
		);

		$dbShare = CIBlockSection::GetList(array(), $filter, false, array('ID'));

		return $dbShare && $dbShare->Fetch();
	}

	public static function createCreatedDocFolder($iblockId, $sectionId, $userId)
	{
		return self::createFolder(self::getNameCreatedDocFolder(), self::XML_ID_CREATED_DOC_FOLDER, $iblockId, $sectionId, $userId);
	}

	protected static function createFolder($name, $xmlId, $iblockId, $sectionId, $userId)
	{
		$filter = array(
			'IBLOCK_ID' => $iblockId,
			'SECTION_ID' => $sectionId,
			'CHECK_PERMISSIONS' => 'N',
			'XML_ID' => $xmlId,
		);
		if($xmlId === null)
		{
			unset($filter['XML_ID']);
			$filter['NAME'] = $name;
		}

		$dbShare = CIBlockSection::GetList(array(), $filter, false, array('ID', 'CREATED_BY', 'XML_ID'));
		if ($dbShare)
		{
			$downloadedFolder = $dbShare->Fetch();
		}
		if(empty($downloadedFolder))
		{
			$se = new CIBlockSection();
			$data = array(
				'IBLOCK_ID' => $iblockId,
				'CHECK_PERMISSIONS' => 'N',
				'XML_ID' => $xmlId,
				'NAME' => $name,
				'IBLOCK_SECTION_ID' => $sectionId,
				'CREATED_BY' => $userId,
				'MODIFIED_BY' => $userId,
			);
			if($xmlId === null)
			{
				unset($data['XML_ID']);
			}

			$newSectionId = $se->Add($data);

			return $newSectionId;
		}
		else
		{
			return $downloadedFolder['ID'];
		}

		return false;
	}

	static function GroupERights($iblockID)
	{
		if (CIBlock::GetArrayByID($iblockID, "RIGHTS_MODE") === "E")
			return;

		$arGroups=self::GetGroups($iblockID);

		foreach ($arGroups as $groupID => $group)
		{
			foreach (self::$ops as $op => $opTrans)
				$arGroups[$groupID]["Operations"][$op] = CSocNetFeaturesPerms::GetOperationPerm(SONET_ENTITY_GROUP, $groupID, 'files', $op);
		}


		$arTasks = CWebDavIblock::GetTasks();
		// set e rights
		$arFields = array(
			'RIGHTS_MODE' => 'E',
			'GROUP_ID' => array()
		);

		$ib = new CIBlock();
		$res = $ib->Update($iblockID, $arFields);

		$ibr = new CIBlockRights($iblockID);
		$rights = array();
		$rights['n0'] = array('GROUP_CODE' => 'G1', 'DO_CLEAN' => 'Y', 'TASK_ID' => $arTasks['X']); // admins
		$rights['n1'] = array('GROUP_CODE' => 'G2', 'DO_CLEAN' => 'Y', 'TASK_ID' => $arTasks['D']); // nobody
		$ibr->SetRights($rights);

		foreach ($arGroups as $groupID => $group)
		{
			$sectionID = $group['SECTION'];

			$ibrs = new CIBlockSectionRights($iblockID, $sectionID);
			$rights = array();
			$i=0;
			foreach($group["Operations"] as $op => $subj)
			{

//			  'Operations' =>
//				array
//				  'view' => string 'A' (length=1)
//				  'write_limited' => string 'Z' (length=1)
//				  'bizproc' => null
//				  'write' => string 'Z' (length=1)
//
//			A>Только владелец группы
//			E>Владелец группы и модераторы группы
//			K>Все члены группы
//			L>Авторизованные пользователи
//			N>Все посетители

				if ($subj)
				{
					if ($subj == "N")
						$sSubj = "G2";
					elseif ($subj == "L")
						$sSubj = "AU";
					else
						$sSubj = "SG{$groupID}_{$subj}";

					$rights[$sSubj] = $arTasks[self::$ops[$op]];
					if (self::$ops[$op] == 'E')
						$rights["CR"] = $arTasks['W'];
				}
			}
			$rights["SG{$groupID}_A"] = $arTasks["X"]; // admin of group
			$arRights = array();
			$i = 0;
			foreach($rights as $subj => $task)
			{
				$arRights['n'.$i++] = array(
					'GROUP_CODE' => $subj,
					'TASK_ID' => $task,
					'DO_CLEAN' => 'NOT'
				);
			}
			$ibrs->SetRights($arRights);
		}
	}

	public function GetUserMaxPermission($ownerType, $ownerId, $userId, $iblockId)
	{
		$arParameters = array(
			"PERMISSION" => "D",
			"CHECK_CREATOR" => "N");
		$ownerId = intVal($ownerId);
		$userId = intVal($userId);
		$iblockId = intval($iblockId);

		if (!in_array($ownerType, array("user", "group")) || $ownerId <= 0 || $iblockId <= 0):
			return $arParameters;
		elseif ($GLOBALS["USER"]->IsAuthorized() && $GLOBALS["USER"]->GetID() == $userId && CSocNetUser::IsCurrentUserModuleAdmin()):
			$arParameters["PERMISSION"] = "X";
			return $arParameters;
		endif;

		$bBizproc = false;
		if (CModule::IncludeModule("bizproc") && CIBlock::GetArrayByID($iblockId, "BIZPROC") != "N"):
			$bBizproc = true;
		endif;

		$arParameters["PERMISSION"] = "X";
		/*
		if ($ownerType == "user"):
			if ($userId == $ownerId):
				$arParameters["PERMISSION"] = "X";
			elseif (CSocNetFeaturesPerms::CanPerformOperation(
				$userId,
				SONET_ENTITY_USER,
				$ownerId,
				"files",
				"write",
				CSocNetUser::IsCurrentUserModuleAdmin())):
				$arParameters["PERMISSION"]	= "W";
			elseif ($bBizproc && CSocNetFeaturesPerms::CanPerformOperation(
				$userId,
				SONET_ENTITY_USER,
				$ownerId,
				"files",
				"bizproc",
				CSocNetUser::IsCurrentUserModuleAdmin())):
				$arParameters["PERMISSION"]	= "U";
			elseif (CSocNetFeaturesPerms::CanPerformOperation(
				$userId,
				SONET_ENTITY_USER,
				$ownerId,
				"files",
				"write_limited",
				CSocNetUser::IsCurrentUserModuleAdmin())):
				$arParameters["PERMISSION"]	= "W";
				$arParameters["CHECK_CREATOR"] = "Y";
			elseif (CSocNetFeaturesPerms::CanPerformOperation(
				$userId,
				SONET_ENTITY_USER,
				$ownerId,
				"files",
				"view",
				CSocNetUser::IsCurrentUserModuleAdmin())):
				$arParameters["PERMISSION"]	= "R";
			endif;
		elseif ($ownerType == "group"):
			if (CSocNetUserToGroup::GetUserRole($userId, $ownerId) == SONET_ROLES_OWNER):
				$arParameters["PERMISSION"]	= "X";
			elseif (CSocNetFeaturesPerms::CanPerformOperation(
				$userId,
				SONET_ENTITY_GROUP,
				$ownerId,
				"files",
				"write",
				CSocNetUser::IsCurrentUserModuleAdmin())):
				$arParameters["PERMISSION"]	= "W";
			elseif ($bBizproc && CSocNetFeaturesPerms::CanPerformOperation(
				$userId,
				SONET_ENTITY_GROUP,
				$ownerId,
				"files",
				"bizproc",
				CSocNetUser::IsCurrentUserModuleAdmin())):
				$arParameters["PERMISSION"]	= "U";
			elseif (CSocNetFeaturesPerms::CanPerformOperation(
				$userId,
				SONET_ENTITY_GROUP,
				$ownerId,
				"files",
				"write_limited",
				CSocNetUser::IsCurrentUserModuleAdmin())):
				$arParameters["PERMISSION"]	= "W";
				$arParameters["CHECK_CREATOR"] = "Y";
			elseif (CSocNetFeaturesPerms::CanPerformOperation(
				$userId,
				SONET_ENTITY_GROUP,
				$ownerId,
				"files",
				"view",
				CSocNetUser::IsCurrentUserModuleAdmin())):
				$arParameters["PERMISSION"]	= "R";
			endif;
		endif;
		 */
		return $arParameters;
	}

	protected static function groupSectionCreate($groupId)
	{
		$groupLib = CWebDavIblock::LibOptions('group_files', false, SITE_ID);
		if(!$groupLib || !isset($groupLib['id']) || !($iblockId = intval($groupLib['id'])))
		{
			return false;
		}

		$result = CIBlockWebdavSocnet::GetSectionID($iblockId, 'group', $groupId);
		if (intval($result) > 0)
		{
			return $result;
		}
		
		$dbRes = CUserTypeEntity::GetList(array($by=>$order), array("ENTITY_ID" => "IBLOCK_".$iblockId."_SECTION", "FIELD_NAME" => "UF_USE_BP"));
		if (!$dbRes || !($res = $dbRes->GetNext()))
		{
			$arFields = array(
				"ENTITY_ID" => "IBLOCK_".$iblockId."_SECTION",
				"FIELD_NAME" => "UF_USE_BP",
				"USER_TYPE_ID" => "string",
				"MULTIPLE" => "N",
				"MANDATORY" => "N",
				"SETTINGS" => array("DEFAULT_VALUE" => "Y"));
			$arFieldName = array();
			$rsLanguage = CLanguage::GetList($by, $order, array());
			while($arLanguage = $rsLanguage->Fetch())
			{
				$dir = str_replace(array("\\", "//"), "/", dirname(__FILE__));
				$dirs = explode("/", $dir);
				array_pop($dirs);
				$file = trim(implode("/", $dirs)."/lang/".$arLanguage["LID"]."/include/webdav_settings.php");
				$tmp_mess = __IncludeLang($file, true);
				$arFieldName[$arLanguage["LID"]] = (empty($tmp_mess["SONET_UF_USE_BP"]) ? "Use Business Process" : $tmp_mess["SONET_UF_USE_BP"]);
			}
			$arFields["EDIT_FORM_LABEL"] = $arFieldName;
			$obUserField  = new CUserTypeEntity;
			$obUserField->Add($arFields);
			$GLOBALS["USER_FIELD_MANAGER"]->arFieldsCache = array();
		}

		$arFields = array(
			"IBLOCK_ID" => $iblockId,
			"ACTIVE" => "Y",
			"SOCNET_GROUP_ID" => false,
			"IBLOCK_SECTION_ID" => 0,
			"UF_USE_BP" => "N"
		);

		$arFields["SOCNET_GROUP_ID"] = $groupId;

		$arFields["NAME"] = GetMessage("SONET_GROUP_PREFIX").$groupId;

		$dbGroup = CSocNetGroup::GetList(
			array(),
			array("ID" => (int) $groupId),
			false,
			false,
			array("ID", "SITE_ID", "NAME")
		);

		if ($arGroup = $dbGroup->Fetch())
		{
			$arFields["NAME"] = GetMessage("SONET_GROUP_PREFIX").$arGroup["NAME"];
		}

		if (CIBlock::GetarrayByID($iblockId, "RIGHTS_MODE") === "E")
		{
			$arTasks = CWebDavIblock::GetTasks();
			$arFields['RIGHTS'] = array(
				'n0' => array('GROUP_CODE' => 'SG'.$arFields["SOCNET_GROUP_ID"].'_A', 'TASK_ID' => $arTasks['X']),
				'n1' => array('GROUP_CODE' => 'SG'.$arFields["SOCNET_GROUP_ID"].'_E', 'TASK_ID' => $arTasks['W']),
				'n2' => array('GROUP_CODE' => 'SG'.$arFields["SOCNET_GROUP_ID"].'_K', 'TASK_ID' => $arTasks['W'])
			);
		}

		$GLOBALS["UF_USE_BP"] = $arFields["UF_USE_BP"];
		$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("IBLOCK_".$iblockId."_SECTION", $arFields);
		$bs = new CIBlockSection;
		$sectionId = $bs->Add($arFields);
		if (!$sectionId)
		{
			return false;
		}

		return $sectionId;
	}
}