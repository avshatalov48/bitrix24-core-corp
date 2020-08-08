<?php
namespace Bitrix\Crm\Integration;
use Bitrix\Main;

class StorageManager
{
	public static function getDefaultTypeID()
	{
		return StorageType::getDefaultTypeID();
	}
	/**
	 * @param array $fileData
	 * @param int $storageTypeID
	 * @param string $siteID
	 * @return array|null
	 */
	public static function getFileInfo($fileID, $storageTypeID = 0, $checkPermissions = true, $options = null)
	{
		if(!is_integer($storageTypeID))
		{
			$storageTypeID = (int)$storageTypeID;
		}

		if(!StorageType::isDefined($storageTypeID))
		{
			$storageTypeID = StorageType::getDefaultTypeID();
		}

		if($storageTypeID === StorageType::Disk)
		{
			return DiskManager::getFileInfo($fileID, $checkPermissions, $options);
		}
		elseif($storageTypeID === StorageType::WebDav)
		{
			return \CCrmWebDavHelper::getElementInfo($fileID, $checkPermissions);
		}
		elseif($storageTypeID === StorageType::File)
		{
			$fileInfo = \CFile::GetFileArray($fileID);
			if(!is_array($fileInfo))
			{
				return null;
			}

			return array(
				'ID' => $fileID,
				'NAME' => isset($fileInfo['ORIGINAL_NAME']) ? $fileInfo['ORIGINAL_NAME'] : $fileID,
				'SIZE' => \CFile::FormatSize($fileInfo['FILE_SIZE'] ? $fileInfo['FILE_SIZE'] : 0),
				'VIEW_URL' => isset($fileInfo['SRC']) ? $fileInfo['SRC'] : ''
			);
		}

		throw new Main\NotSupportedException("Storage type: '{$storageTypeID}' is not supported in current context");
	}
	/**
	 * @param array $fileData
	 * @param string $siteID
	 * @return int|false
	 */
	public static function saveEmailAttachment(array $fileData, $storageTypeID = 0, $siteID = '', $params = array())
	{
		if(!is_integer($storageTypeID))
		{
			$storageTypeID = (int)$storageTypeID;
		}

		if(!StorageType::isDefined($storageTypeID))
		{
			$storageTypeID = StorageType::getDefaultTypeID();
		}

		if($storageTypeID === StorageType::Disk)
		{
			$params['USE_MONTH_FOLDERS'] = true;
			return DiskManager::saveEmailAttachment($fileData, $siteID, $params);
		}
		elseif($storageTypeID === StorageType::WebDav)
		{
			return \CCrmWebDavHelper::saveEmailAttachment($fileData, $siteID, $params);
		}

		throw new Main\NotSupportedException("Storage type: '{$storageTypeID}' is not supported in current context");
	}
	/**
	 * @param array $fileData
	 * @param string $siteID
	 * @param array $params
	 * @return int|false
	 */
	public static function saveFile(array $fileData, $storageTypeID = 0, $siteID = '', $params = array())
	{
		if(!is_integer($storageTypeID))
		{
			$storageTypeID = (int)$storageTypeID;
		}

		if(!StorageType::isDefined($storageTypeID))
		{
			$storageTypeID = StorageType::getDefaultTypeID();
		}

		if($storageTypeID === StorageType::Disk)
		{
			return DiskManager::saveFile($fileData, $siteID, $params);
		}
		elseif($storageTypeID === StorageType::WebDav)
		{
			return \CCrmWebDavHelper::saveFile($fileData, $siteID, $params);
		}

		throw new Main\NotSupportedException("Storage type: '{$storageTypeID}' is not supported in current context");
	}

	public static function deleteFile($fileID, $storageTypeID = 0)
	{
		if($storageTypeID === StorageType::File)
		{
			\CFile::Delete($fileID);
		}
		elseif($storageTypeID === StorageType::Disk)
		{
			Main\Loader::includeModule('disk');

			$codeMap = array(
				StorageFileType::getFolderXmlID(StorageFileType::EmailAttachment) => true,
				StorageFileType::getFolderXmlID(StorageFileType::CallRecord) => true,
				StorageFileType::getFolderXmlID(StorageFileType::Rest) => true
			);

			$file = \Bitrix\Disk\File::loadById($fileID);
			if($file !== null)
			{
				$folder = $file->getParent();
				if($folder !== null)
				{
					if((isset($codeMap[$folder->getXmlId()]) || $folder->getCode() === \Bitrix\Disk\SpecificFolder::CODE_FOR_UPLOADED_FILES)
						&& $file->countAttachedObjects() == 0
					)
					{
						$file->delete(\Bitrix\Disk\SystemUser::SYSTEM_USER_ID);
					}
				}
			}
		}
	}

	/**
	 * @param int|array $fileID
	 * @param int $storageTypeID
	 * @return array|null
	 */
	public static function makeFileArray($fileID, $storageTypeID)
	{
		if(!is_integer($storageTypeID))
		{
			$storageTypeID = (int)$storageTypeID;
		}

		if($storageTypeID === StorageType::Disk)
		{
			if(!is_array($fileID))
			{
				return DiskManager::makeFileArray($fileID);
			}

			$result = array();
			foreach($fileID as $ID)
			{
				$ary = DiskManager::makeFileArray($ID);
				if(is_array($ary))
				{
					$result[] = $ary;
				}
			}
			return $result;
		}
		elseif($storageTypeID === StorageType::WebDav)
		{
			if(!is_array($fileID))
			{
				return \CCrmWebDavHelper::makeElementFileArray($fileID);
			}

			$result = array();
			foreach($fileID as $ID)
			{
				$ary = \CCrmWebDavHelper::makeElementFileArray($ID);
				if(is_array($ary))
				{
					$result[] = $ary;
				}
			}
			return $result;
		}
		elseif($storageTypeID === StorageType::File)
		{
			if(!is_array($fileID))
			{
				return \CFile::makeFileArray($fileID);
			}

			$result = array();
			foreach($fileID as $ID)
			{
				$ary = \CFile::makeFileArray($ID);
				if(is_array($ary))
				{
					$result[] = $ary;
				}
			}
			return $result;
		}

		throw new Main\NotSupportedException("Storage type: '{$storageTypeID}' is not supported in current context");
	}
	/**
	 * @param int $fileID
	 * @param int $storageTypeID
	 * @return string
	 */
	public static function getFileName($fileID, $storageTypeID)
	{
		if(!is_integer($fileID))
		{
			$storageTypeID = (int)$fileID;
		}

		if(!is_integer($storageTypeID))
		{
			$storageTypeID = (int)$storageTypeID;
		}

		if($storageTypeID === StorageType::Disk)
		{
			return DiskManager::getFileName($fileID);
		}
		elseif($storageTypeID === StorageType::WebDav)
		{
			$info = \CCrmWebDavHelper::GetElementInfo($fileID, false);
			return is_array($info) && isset($info['NAME']) ? $info['NAME'] : "[{$fileID}]";
		}
		elseif($storageTypeID === StorageType::File)
		{
			$info = \CFile::GetFileArray($fileID);
			return is_array($info) && isset($info['FILE_NAME']) ? $info['FILE_NAME'] : "[{$fileID}]";
		}

		throw new Main\NotSupportedException("Storage type: '{$storageTypeID}' is not supported in current context");
	}
	/**
	 * @param int $fileID
	 * @param int $storageTypeID
	 * @return boolean
	 */
	public static function checkFileReadPermission($fileID, $storageTypeID, $userID = 0)
	{
		if(!is_integer($fileID))
		{
			$storageTypeID = (int)$fileID;
		}

		if(!is_integer($storageTypeID))
		{
			$storageTypeID = (int)$storageTypeID;
		}

		if($storageTypeID === StorageType::WebDav)
		{
			return \CCrmWebDavHelper::CheckElementReadPermission($fileID, $userID);
		}
		elseif($storageTypeID === StorageType::Disk)
		{
			return DiskManager::checkFileReadPermission($fileID, $userID);
		}
		elseif($storageTypeID === StorageType::File)
		{
			return true;
		}

		throw new Main\NotSupportedException("Storage type: '{$storageTypeID}' is not supported in current context");
	}

	public static function registerInterRequestFile($fileID, $storageTypeID)
	{
		if($storageTypeID === StorageType::WebDav)
		{
			if (!isset($_SESSION['crm_saved_dav_files']))
			{
				$_SESSION['crm_saved_dav_files'] = array();
			}
			$_SESSION['crm_saved_dav_files'][] = $fileID;
		}
		elseif($storageTypeID === StorageType::Disk)
		{
			if (!isset($_SESSION['crm_saved_disk_files']))
			{
				$_SESSION['crm_saved_disk_files'] = array();
			}
			$_SESSION['crm_saved_disk_files'][] = $fileID;
		}
	}
	public static function getInterRequestFiles($storageTypeID)
	{
		if($storageTypeID === StorageType::WebDav)
		{
			return isset($_SESSION['crm_saved_dav_files']) ? $_SESSION['crm_saved_dav_files'] : array();
		}
		elseif($storageTypeID === StorageType::Disk)
		{
			return isset($_SESSION['crm_saved_disk_files']) ? $_SESSION['crm_saved_disk_files'] : array();
		}
		return array();
	}

	public static function filterFiles(array $fileIDs, $storageTypeID, $userID = 0)
	{
		if(!is_integer($storageTypeID))
		{
			$storageTypeID = (int)$storageTypeID;
		}

		$savedFiles = self::getInterRequestFiles($storageTypeID);
		$result = array();
		if($storageTypeID === StorageType::WebDav)
		{
			foreach($fileIDs as $fileID)
			{
				if(in_array($fileID, $savedFiles) || \CCrmWebDavHelper::CheckElementReadPermission($fileID, $userID))
				{
					$result[] = $fileID;
				}
			}
		}
		elseif($storageTypeID === StorageType::Disk)
		{
			foreach($fileIDs as $fileID)
			{
				if(in_array($fileID, $savedFiles) || DiskManager::checkFileReadPermission($fileID, $userID))
				{
					$result[] = $fileID;
				}
			}
		}
		elseif($storageTypeID === StorageType::File)
		{
			$result = $fileIDs;
		}

		return $result;
	}
}