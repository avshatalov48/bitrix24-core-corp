<?php
/*
 * CVoxImplantWebDavHelper - integration with webdav module.
 * */
class CVoxImplantWebDavHelper
{
	private static $URL_TEMPLATES = null;
	private static $IBLOCK_ELEMENTS = null;
	private static $IBLOCK_SECTION_NAV_CHAINS = null;
	private static $IBLOCK = array();

	private static function GetIBlock($iblockID)
	{
		if (!(IsModuleInstalled('iblock')
			&& IsModuleInstalled('webdav')
			&& CModule::IncludeModule('iblock')
			&& CModule::IncludeModule('webdav')))
		{
			return null;
		}

		$iblockID = intval($iblockID);
		if(!isset(self::$IBLOCK[$iblockID]))
		{
			self::$IBLOCK[$iblockID] = new CWebDavIblock($iblockID, '');
		}

		return self::$IBLOCK[$iblockID];
	}

	private static function GetElement($elementID)
	{
		if (!(IsModuleInstalled('iblock')
			&& CModule::IncludeModule('iblock')))
		{
			return null;
		}

		$elementID = intval($elementID);

		if(is_array(self::$IBLOCK_ELEMENTS) && isset(self::$IBLOCK_ELEMENTS[$elementID]))
		{
			return self::$IBLOCK_ELEMENTS[$elementID];
		}

		if(self::$IBLOCK_ELEMENTS === null)
		{
			self::$IBLOCK_ELEMENTS = array();
		}

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

		self::$IBLOCK_ELEMENTS[$elementID] = is_object($dbElement) ? $dbElement->Fetch() : null;
		return self::$IBLOCK_ELEMENTS[$elementID];
	}

	public static function CheckElementReadPermission($elementID)
	{
		if (!(IsModuleInstalled('iblock')
			&& IsModuleInstalled('webdav')
			&& CModule::IncludeModule('iblock')
			&& CModule::IncludeModule('webdav')))
		{
			return false;
		}

		$arElement = self::GetElement($elementID);
		if(!$arElement)
		{
			return false;
		}

		$arIblock = self::GetIBlock($arElement['IBLOCK_ID']);
		if(!$arIblock)
		{
			return false;
		}

		return $arIblock->CheckWebRights(
			'',
			array(
				'action' => 'read',
				'arElement' =>
					array(
						'ID' => $elementID,
						'item_id' => $elementID,
						'is_dir' => false,
						'not_found' => false
					)
			),
			false
		);
	}

	public static function GetElementInfo($elementID, $checkPermissions = true)
	{
		if (!(IsModuleInstalled('iblock')
			&& IsModuleInstalled('webdav')
			&& CModule::IncludeModule('iblock')
			&& CModule::IncludeModule('webdav')))
		{
			return array();
		}

		if($checkPermissions && !self::CheckElementReadPermission($elementID))
		{
			return array();
		}

		$arElement = self::GetElement($elementID);
		if(!$arElement)
		{
			return array();
		}

		if(self::$URL_TEMPLATES === null && method_exists('CWebDavIblock', 'GetULRsFromIBlockID'))
		{
			self::$URL_TEMPLATES = CWebDavIblock::GetULRsFromIBlockID($arElement['IBLOCK_ID']);
		}

		$showUrlTemplate = '';
		$viewUrlTemplate = '';
		$editUrlTemplate = '';
		$deleteUrlTemplate = '';

		if(is_array(self::$URL_TEMPLATES) && !empty(self::$URL_TEMPLATES))
		{
			if(isset(self::$URL_TEMPLATES['view']))
			{
				$showUrlTemplate = self::$URL_TEMPLATES['view'];
			}

			if(isset(self::$URL_TEMPLATES['history_get']))
			{
				$viewUrlTemplate = self::$URL_TEMPLATES['history_get'];
			}

			if(isset(self::$URL_TEMPLATES['edit']))
			{
				$editUrlTemplate = self::$URL_TEMPLATES['edit'];
			}

			if(isset(self::$URL_TEMPLATES['delete_dropped']))
			{
				$deleteUrlTemplate = self::$URL_TEMPLATES['delete_dropped'];
			}
		}

		if($showUrlTemplate === '')
		{
			$showUrlTemplate = CWebDavIblock::LibOptions('lib_paths', true, $arElement['IBLOCK_ID']);
			if(!is_string($showUrlTemplate))
			{
				$showUrlTemplate = '';
			}
		}

		if($showUrlTemplate === '')
		{
			//HACK: Build default paths.
			if(\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
			{
				$showUrlTemplate = '/docs/element/view/#ELEMENT_ID#/';
				$viewUrlTemplate = '/docs/element/historyget/#ELEMENT_ID#/#ELEMENT_NAME#';
				$editUrlTemplate = '/docs/element/edit/edit/#ELEMENT_ID#/';
			}
			else
			{
				$showUrlTemplate = SITE_DIR.'docs/shared/element/view/#ELEMENT_ID#/';
				$viewUrlTemplate = SITE_DIR.'docs/shared/element/historyget/#ELEMENT_ID#/#ELEMENT_NAME#';
				$editUrlTemplate = SITE_DIR.'docs/shared/element/edit/edit/#ELEMENT_ID#/';
			}
		}

		$showUrl = self::PrepareUrl($showUrlTemplate, $arElement);
		$viewUrl = $viewUrlTemplate !== ''
			? self::PrepareUrl($viewUrlTemplate, $arElement)
			: str_replace('view', 'historyget', $showUrl);
		$editUrl = $editUrlTemplate !== ''
			? self::PrepareUrl($editUrlTemplate, $arElement)
			: str_replace('view', 'edit', $showUrl).'EDIT/';

		if ($deleteUrlTemplate !== '')
		{
			$deleteUrl = self::PrepareUrl($deleteUrlTemplate, $arElement);
		}
		else if (preg_match('/\/docs\/shared\//i', $showUrl))
		{
			$deleteUrl = '';
		}
		else
		{
			$deleteUrl = str_replace('view', 'edit', $showUrl).'DELETE_DROPPED/';
		}

		$size = '';
		$dbSize = CIBlockElement::GetProperty($arElement['IBLOCK_ID'], $arElement['ID'], array(), array('CODE' => 'WEBDAV_SIZE'));
		if ($dbSize && $arSize=$dbSize->Fetch())
		{
			$size = CFile::FormatSize($arSize['VALUE']);
		}

		return array(
			'ID' => $elementID,
			'NAME' => $arElement['NAME'],
			'EDIT_URL' => $editUrl,
			'VIEW_URL' => $viewUrl,
			'DELETE_URL' => $deleteUrl,
			'SHOW_URL' => $showUrl,
			'SIZE' => $size
		);
	}

	private static function PrepareUrl($template, &$arElement)
	{
		if (!(IsModuleInstalled('iblock')
			&& CModule::IncludeModule('iblock')))
		{
			return '';
		}

		$template = strval($template);
		if($template === '' || !is_array($arElement))
		{
			return '';
		}

		$elementID = isset($arElement['ID']) ? intval($arElement['ID']) : 0;
		$elementName = isset($arElement['NAME']) ? $arElement['NAME'] : '';
		$authorID = isset($arElement['CREATED_BY']) ? intval($arElement['CREATED_BY']) : 0;

		$navChainKey = $arElement['IBLOCK_ID'].'_'.$arElement['IBLOCK_SECTION_ID'];
		if(is_array(self::$IBLOCK_SECTION_NAV_CHAINS) && isset(self::$IBLOCK_SECTION_NAV_CHAINS[$navChainKey]))
		{
			$arSection = self::$IBLOCK_SECTION_NAV_CHAINS[$navChainKey];
		}
		else
		{
			if(self::$IBLOCK_SECTION_NAV_CHAINS === null)
			{
				self::$IBLOCK_SECTION_NAV_CHAINS = array();
			}

			$dbNav = CIBlockSection::GetNavChain($arElement['IBLOCK_ID'], $arElement['IBLOCK_SECTION_ID']);
			$arSection = self::$IBLOCK_SECTION_NAV_CHAINS[$navChainKey] = is_object($dbNav) ? $dbNav->Fetch() : null;
		}

		$socnetGroupID = is_array($arSection) && isset($arSection['SOCNET_GROUP_ID']) ? intval($arSection['SOCNET_GROUP_ID']) : 0;

		$url = $template;

		$url = str_replace(
			array(
				'#ELEMENT_ID#',
				'#element_id#',
				'#ID#',
				'#id#'
			),
			$elementID,
			$url
		);

		$url = str_replace(
			array(
				'#ELEMENT_NAME#',
				'#element_name#',
				'#NAME#',
				'#name#'
			),
			$elementName,
			$url
		);

		$url = str_replace(
			array(
				'#SOCNET_USER_ID#',
				'#socnet_user_id#',
				'#USER_ID#',
				'#user_id#'
			),
			$authorID,
			$url
		);

		$url = str_replace(
			array(
				'#SOCNET_GROUP_ID#',
				'#socnet_group_id#',
				'#GROUP_ID#',
				'#group_id#',
			),
			$socnetGroupID,
			$url
		);

		$url = str_replace(
			array(
				'#SOCNET_OBJECT#',
				'#socnet_object#'
			),
			$socnetGroupID > 0 ? 'group' : 'user',
			$url
		);

		$url = str_replace(
			array(
				'#SOCNET_OBJECT_ID#',
				'#socnet_object_id#'
			),
			$socnetGroupID > 0 ? $socnetGroupID : $authorID,
			$url
		);

		return str_replace(array("///","//"),"/", $url);
	}

	public static function MakeElementFileArray($elementID)
	{
		if (!(IsModuleInstalled('iblock')
			&& IsModuleInstalled('webdav')
			&& CModule::IncludeModule('iblock')
			&& CModule::IncludeModule('webdav')))
		{
			return 0;
		}

		$elementID = intval($elementID);

		$arElement = self::GetElement($elementID);
		if(!$arElement)
		{
			return null;
		}

		$fileID = self::GetIBlock($arElement['IBLOCK_ID'])->GetHistoryFileID($elementID);
		if($fileID <= 0)
		{
			return null;
		}

		$arRawFile = CFile::MakeFileArray($fileID);
		if(is_array($arRawFile) && !empty($arElement['NAME']))
		{
			$arRawFile['name'] = $arElement['NAME'];
		}

		return $arRawFile;
	}

	private static function ResolveSharedFileIBlockID($siteID = SITE_ID)
	{
		$siteID = strval($siteID);
		if($siteID === '')
		{
			return 0;
		}

		$blockID = 0;
		$sharedFilesSettings = unserialize(COption::GetOptionString('webdav', 'shared_files', ''), ['allowed_classes' => false]);
		if(isset($sharedFilesSettings[$siteID]))
		{
			$siteSettings = $sharedFilesSettings[$siteID];
			$blockID = isset($siteSettings['id']) ? intval($siteSettings['id']) : 0;
		}

		if($blockID <= 0)
		{
			$dbIBlock = CIBlock::GetList(array(), array('XML_ID' => "shared_files_{$siteID}", 'TYPE' => 'library'));
			if ($arIBlock = $dbIBlock->Fetch())
			{
				$blockID = $arIBlock['ID'];
			}
		}

		return $blockID;
	}

	private static function EnsureSharedFileSectionCreated($blockID, $siteID = SITE_ID)
	{
		$siteID = strval($siteID);
		$blockID = intval($blockID);
		if($blockID <= 0 || $siteID === '')
		{
			return 0;
		}

		$blockSection = new CIBlockSection();
		$dbSections = $blockSection->GetList(array(), array('XML_ID' => 'VI_CALLS', 'IBLOCK_ID'=> $blockID, 'CHECK_PERMISSIONS' => 'N'), false, array('ID'));
		$arSection = $dbSections->Fetch();
		if(is_array($arSection))
		{
			$blockSectionID = intval($arSection['ID']);
		}

		if($blockSectionID <= 0)
		{
			$dbSite = CSite::GetByID($siteID);
			$arSite = $dbSite->Fetch();
			IncludeModuleLangFile(__FILE__, $arSite && isset($arSite['LANGUAGE_ID']) ? $arSite['LANGUAGE_ID'] : false);

			$blockSectionID = $blockSection->Add(
				array(
					'IBLOCK_ID' => $blockID,
					'ACTIVE' => 'Y',
					'NAME' => GetMessage('VI_DISK_CALL_RECORD_SECTION'),
					'IBLOCK_SECTION_ID' => 0,
					'CHECK_PERMISSIONS' => 'N',
					'XML_ID' => 'VI_CALLS'
				)
			);

			if (CIBlock::GetArrayByID($blockID, "RIGHTS_MODE") === "E")
			{
				$rightObject = CWebDavIblock::_get_ib_rights_object('IBLOCK', 0, $blockID);
				$existsRights = $rightObject->GetRights();

				$rs = CTask::GetList(
					array("LETTER"=>"asc"),
					array(
						"MODULE_ID" => "iblock",
						"BINDING" => "iblock",
						"SYS" => "Y",
					)
				);
				$arTasks = array();
				while($ar = $rs->Fetch())
					$arTasks[$ar["NAME"]] = $ar["ID"];

				$newRights = array();
				$i = 0;
				foreach ($existsRights as $existsRight)
				{
					$newRights['n'.$i] = array(
						'GROUP_CODE' => $existsRight['GROUP_CODE'],
						'TASK_ID' => $arTasks['iblock_deny'],
					);
					$i++;
				}
				$rightObject = CWebDavIblock::_get_ib_rights_object('SECTION', $blockSectionID, $blockID);
				$rightObject->SetRights($newRights);

				$rights['n'.$i] = array(
					'GROUP_CODE' => '',
					'TASK_ID' => $arTasks['iblock_deny'],
				);
				CWebDavIblock::appendRightsOnSections(Array(Array(
					'ID' => $blockSectionID,
					'IBLOCK_ID' => $blockID,
				)), Array(
					'W' => Array('G1'),
				));
			}
		}

		return $blockSectionID;
	}

	public static function SaveFile($arHistory, $arFile, $siteID = SITE_ID)
	{
		if (!(IsModuleInstalled('iblock')
			&& CModule::IncludeModule('iblock')))
		{
			return false;
		}

		$siteID = strval($siteID);
		if($siteID === '')
		{
			if(!(defined('ADMIN_SECTION') && ADMIN_SECTION))
			{
				$siteID = SITE_ID;
			}
			else
			{
				$dbSites = CSite::GetList('sort', 'desc', array('DEF' => 'Y'));
				while($arSite = $dbSites->Fetch())
				{
					$siteID = $arSite['LID'];
				}
			}
		}

		if($siteID === '')
		{
			return false;
		}

		$blockID = self::ResolveSharedFileIBlockID($siteID);
		if($blockID <= 0)
		{
			return false;
		}

		$blockSectionID = self::EnsureSharedFileSectionCreated($blockID, $siteID);
		if($blockSectionID <= 0)
		{
			return false;
		}

		$fileInfo = pathinfo($arFile['ORIGINAL_NAME']);
		$fileInfo['filename'] = $arHistory['CALL_START_DATE']->format("Y-m-d_h-i-s__").$arHistory['PHONE_NUMBER'];
		$elementName = isset($fileInfo['extension']) ? "{$fileInfo['filename']}.{$fileInfo['extension']}" : "{$fileInfo['filename']}";

		$element = new CIBlockElement();
		$alreadyExists = false;
		$i = 0;
		do
		{
			if($alreadyExists)
			{
				$i++;
				$elementName  = isset($fileInfo['extension']) ? "{$fileInfo['filename']}_{$i}.{$fileInfo['extension']}" : "{$fileInfo['filename']}_{$i}";
			}

			$dbRes = $element->GetList(array(), array('=NAME' => $elementName, 'IBLOCK_ID'=> $blockID, 'IBLOCK_SECTION_ID'=> $blockSectionID), false, array('nTopCount'=>1), array('ID'));
			$arRes = $dbRes ? $dbRes->Fetch() : false;
			$alreadyExists = $arRes !== false;
		} while($alreadyExists);

		$arFields = array(
			'ACTIVE' => 'Y',
			'IBLOCK_ID' => $blockID,
			'IBLOCK_SECTION_ID' => $blockSectionID,
			'NAME' => $elementName,
			'WF_COMMENTS' => '',
			'PROPERTY_VALUES' => array(
				'FILE' => $arFile,
				'WEBDAV_SIZE' => $arFile['FILE_SIZE']
			),
		);
		$elementId = $element->Add($arFields, false, true, false);

		$arRights = Array('G1');
		if ($arHistory['PORTAL_USER_ID'] > 0)
			$arRights[] = 'U'.$arHistory['PORTAL_USER_ID'];

		CWebDavIblock::appendRightsOnElements(Array(Array(
			'ID' => $elementId,
			'IBLOCK_ID' => $blockID,
		)), Array(
			'W' => $arRights
		));

		return $elementId;
	}
}

class CVoxImplantDiskHelper
{
	public static function Enabled()
	{
		if (!CModule::IncludeModule('disk'))
			return false;

		if (!Bitrix\Disk\Driver::isSuccessfullyConverted())
			return false;

		return true;
	}

	/**
	 * @param string $siteId
	 * @return \Bitrix\Disk\Storage
	 */
	public static function GetStorageModel($siteId = SITE_ID)
	{
		if (!self::Enabled())
			return false;

		if ($siteId === '')
			return false;

		$storageModel = \Bitrix\Disk\Driver::getInstance()->getStorageByCommonId('shared_files_'.$siteId);
		if (!$storageModel)
		{
			return false;
		}
		return $storageModel;
	}

	public static function GetRootFolder($siteId = SITE_ID)
	{
		if (!self::Enabled())
			return false;

		$storageModel = self::GetStorageModel($siteId);
		if (!$storageModel)
		{
			return false;
		}

		$folderModel = \Bitrix\Disk\Folder::load(array(
			'STORAGE_ID' => $storageModel->getId(),
			'PARENT_ID' => $storageModel->getRootObjectId(),
			'TYPE' => \Bitrix\Disk\Internals\ObjectTable::TYPE_FOLDER,
			'=CODE' => 'VI_CALLS',
        ));
		if (!$folderModel)
		{
			// Access codes
			$rightsManager = \Bitrix\Disk\Driver::getInstance()->getRightsManager();
			$fullAccessTaskId = $rightsManager->getTaskIdByName($rightsManager::TASK_FULL);
			$rights = $rightsManager->getAllListNormalizeRights($storageModel->getRootObject());

			$accessCodes = array();
			foreach	($rights as $right)
			{
				$accessCodes[] = Array(
					'ACCESS_CODE' => $right['ACCESS_CODE'],
					'TASK_ID' => $right['TASK_ID'],
					'NEGATIVE' => 1
				);
			}
			$accessCodes[] = Array(
				'ACCESS_CODE' => 'G1',
				'TASK_ID' => $fullAccessTaskId,
			);

			// Folder name
			$dbSite = CSite::GetByID($siteId);
			$arSite = $dbSite->Fetch();
			IncludeModuleLangFile(__FILE__, $arSite && isset($arSite['LANGUAGE_ID']) ? $arSite['LANGUAGE_ID'] : false);

			$folderModel = $storageModel->addFolder(array(
				'NAME' => GetMessage('VI_DISK_CALL_RECORD_SECTION'),
				'CODE' => 'VI_CALLS',
				'CREATED_BY' => \Bitrix\Disk\SystemUser::SYSTEM_USER_ID
			), $accessCodes);

			if (!$folderModel)
			{
				if ($storageModel->getErrorByCode(\Bitrix\Disk\Folder::ERROR_NON_UNIQUE_NAME))
				{
					$folderModel = \Bitrix\Disk\Folder::load(array(
						'STORAGE_ID' => $storageModel->getId(),
						'PARENT_ID' => $storageModel->getRootObjectId(),
						'TYPE' => \Bitrix\Disk\Internals\ObjectTable::TYPE_FOLDER,
						'=NAME' => GetMessage('VI_DISK_CALL_RECORD_SECTION'),
					));
					$folderModel->changeCode('VI_CALLS');
				}
				else
				{
					$folderModel = $storageModel->addFolder(array(
						'NAME' => GetMessage('VI_DISK_CALL_RECORD_SECTION'),
						'CODE' => 'VI_CALLS',
						'CREATED_BY' => \Bitrix\Disk\SystemUser::SYSTEM_USER_ID
					), $accessCodes, true);
				}
			}
		}

		return $folderModel;
	}

	/**
	 * @param \Bitrix\Main\Type\DateTime $recordDate
	 * @return \Bitrix\Disk\Folder|false
	 */
	public static function GetRecordsFolder($folderName, $siteId = SITE_ID)
	{
		if(!\Bitrix\Main\Loader::includeModule('disk'))
			return false;
		
		$rootFolder = self::GetRootFolder($siteId);
		if (!$rootFolder)
		{
			return false;
		}

		$subFolder = \Bitrix\Disk\Folder::load(array(
			'=NAME' => $folderName,
			'PARENT_ID' => $rootFolder->getId(),
		));

		if (!$subFolder)
		{
			$subFolder = $rootFolder->addSubFolder(array(
				'NAME' => $folderName,
				'CREATED_BY' => \Bitrix\Disk\SystemUser::SYSTEM_USER_ID
			));
		}

		return $subFolder;
	}

	public static function CheckParams($arHistory, $arFile)
	{
		if (!($arHistory['CALL_START_DATE'] instanceof Bitrix\Main\Type\DateTime))
			return false;

		if ($arHistory['PHONE_NUMBER'] == '')
			return false;

		if (intval($arFile['ID']) <= 0)
			return false;

		if ($arFile['ORIGINAL_NAME'] == '')
			return false;

		if (intval($arFile['FILE_SIZE']) <= 0)
			return false;

		return true;
	}

	public static function SaveFile($arHistory, $arFile, $siteId = SITE_ID)
	{
		if (!self::Enabled())
		{
			return CVoxImplantWebDavHelper::SaveFile($arHistory, $arFile, $siteId);
		}

		if (!self::CheckParams($arHistory, $arFile))
		{
			return false;
		}

		$portalUserId = (int)$arHistory['PORTAL_USER_ID'];

		$subFolder = self::GetRecordsFolder($arHistory['CALL_START_DATE']->format("Y-m"), $siteId);
		if(!$subFolder)
		{
			return false;
		}
		$accessCodes = Array();
		$rightsManager = \Bitrix\Disk\Driver::getInstance()->getRightsManager();
		$fullAccessTaskId = $rightsManager->getTaskIdByName($rightsManager::TASK_FULL);

		$accessCodes[] = Array(
			'ACCESS_CODE' => $portalUserId > 0 ? 'U'.$portalUserId : 'G1',
			'TASK_ID' => $fullAccessTaskId,
		);

		$fileInfo = pathinfo($arFile['ORIGINAL_NAME']);
		$fileInfo['filename'] = $arHistory['CALL_START_DATE']->format("Y-m-d H-i-s")." ".$arHistory['PHONE_NUMBER'];
		$defaultExtension = "mp3";
		$elementName = isset($fileInfo['extension']) ? "{$fileInfo['filename']}.{$fileInfo['extension']}" : "{$fileInfo['filename']}.{$defaultExtension}";

		$fileModel = $subFolder->addFile(array(
			'NAME' => $elementName,
			'FILE_ID' => (int)$arFile['ID'],
			'SIZE' => (int)$arFile['FILE_SIZE'],
			'CREATED_BY' => $portalUserId,
		), $accessCodes, true);

		if($fileModel instanceof \Bitrix\Disk\File)
			return $fileModel->getId();
		else
			return null;
	}
}
