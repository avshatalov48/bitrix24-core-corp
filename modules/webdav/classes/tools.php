<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

IncludeModuleLangFile(__FILE__);

final class CWebDavTools
{
	const OPT_DESKTOP_DISK_INSTALL                 = 'DesktopDiskInstall';
	const OPT_ALLOW_EXT_DOC_SERVICES_GLOBAL        = 'webdav_allow_ext_doc_services_global';
	const OPT_ALLOW_EXT_DOC_SERVICES_LOCAL         = 'webdav_allow_ext_doc_services_local';
	const OPT_ALLOW_AUTOCONNECT_SHARE_GROUP_FOLDER = 'webdav_allow_autoconnect_share_group_folder';

	const DESKTOP_DISK_STATUS_ONLINE        = 'online';
	const DESKTOP_DISK_STATUS_NOT_INSTALLED = 'not_installed';
	const DESKTOP_DISK_STATUS_NOT_ENABLED   = 'not_enabled';

	protected static $userNameTemplate = "#NAME# #LAST_NAME#";
	protected static $showLogin = true;

	/**
	 * @return string
	 */
	public static function getServiceEditDocForCurrentUser()
	{
		static $service;
		if ($service)
		{
			return $service;
		}
		$userSettings = CUserOptions::GetOption('webdav', 'user_settings', array('service_edit_doc_default' => CWebDavLogOnlineEditBase::GOOGLE_SERVICE_NAME));
		$service = $userSettings['service_edit_doc_default'];

		$service = strtolower($service);
		switch($service)
		{
			case 'g':
			case 'google':
			case 'gdrive':
				$service = CWebDavLogOnlineEditBase::GOOGLE_SERVICE_NAME;
				break;
			case 's':
			case 'skydrive':
			case 'sky-drive':
			case 'onedrive':
				$service = CWebDavLogOnlineEditBase::SKYDRIVE_SERVICE_NAME;
				break;
		}

		return $service;
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public static function getShowOfferBannerForCurrentUser($name)
	{
		$userSettings = CUserOptions::GetOption('webdav', '~banner-offer', array($name => false));

		return !$userSettings[$name];
	}

	public static function setShowOfferBannerForCurrentUser($name, $value)
	{
		global $USER;
		if($USER->getId() <= 0)
		{
			return false;
		}
		return CUserOptions::SetOption('webdav', '~banner-offer', array($name => (bool)$value), false, $USER->getId());
	}

	public static function getServiceEditName($service)
	{
		$service = strtolower($service);
		switch($service)
		{
			case 'g':
			case 'google':
				return GetMessage('WD_SERVICE_NAME_GOOGLE_DRIVE');
				break;
			case 's':
			case 'skydrive':
			case 'sky-drive':
				return GetMessage('WD_SERVICE_NAME_SKYDRIVE');
				break;
		}
		return GetMessage('WD_SERVICE_NAME_GOOGLE_DRIVE');
	}

	public static function getServiceEditNameForCurrentUser()
	{
		return static::getServiceEditName(static::getServiceEditDocForCurrentUser());
	}

	public static function convertFromUtf8(&$data)
	{
		global $APPLICATION;
		static $isUtfInstall = null;

		if($isUtfInstall === null)
		{
			$isUtfInstall = defined('BX_UTF');
		}
		if($isUtfInstall === true)
		{
			return;
		}

		if(is_array($data))
		{
			foreach ($data as &$item)
			{
				static::convertToUtf8($item);
			}
			unset($item);
		}
		elseif(!is_numeric($data) && is_string($data))
		{
			$data = $APPLICATION->convertCharset($data, 'UTF-8', LANG_CHARSET);
		}

	}

	public static function convertToUtf8(&$data)
	{
		global $APPLICATION;
		static $isUtfInstall = null;

		if($isUtfInstall === null)
		{
			$isUtfInstall = defined('BX_UTF');
		}
		if($isUtfInstall === true)
		{
			return;
		}

		if(is_array($data))
		{
			foreach ($data as &$item)
			{
				static::convertToUtf8($item);
			}
			unset($item);
		}
		elseif(!is_numeric($data) && is_string($data))
		{
			$data = $APPLICATION->convertCharset($data, LANG_CHARSET, 'UTF-8');
		}
	}

	public static function sendJsonResponse($response, $httpStatusCode = null, $afterEchoCallback = null)
	{
		global $APPLICATION;
		$APPLICATION->restartBuffer();
		while(ob_end_clean());

		if($httpStatusCode == 403)
		{
			header('HTTP/1.0 403 Forbidden', true, 403);
		}
		if($httpStatusCode == 500)
		{
			header('HTTP/1.0 500 Internal Server Error', true, 500);
		}
		header('Content-Type:application/json; charset=UTF-8');
		CWebDavTools::convertToUtf8($response);
		echo json_encode($response);

		if($afterEchoCallback !== null && is_callable($afterEchoCallback))
		{
			call_user_func_array($afterEchoCallback, array());
		}

		require_once($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/epilog_after.php');
		die;
	}

	public static function isDesktopDiskInstall()
	{
		return CUserOptions::GetOption('webdav', static::OPT_DESKTOP_DISK_INSTALL);
	}

	public static function isDesktopDiskOnline()
	{
		return static::isDesktopImOnline() && static::isDesktopDiskInstall();
	}

	public static function enableInVersion($version)
	{
		if(!CModule::IncludeModule('im'))
		{
			return false;
		}
		return CIMMessenger::EnableInVersion($version);
	}

	public static function isDesktopInstall()
	{
		if(!CModule::IncludeModule('im'))
		{
			return false;
		}
		return CIMMessenger::CheckInstallDesktop();
	}

	public static function isDesktopImOnline()
	{
		if(!CModule::IncludeModule('im'))
		{
			return false;
		}
		return CIMMessenger::CheckDesktopStatusOnline();
	}

	public static function setDesktopDiskInstalled()
	{
		global $USER;
		CUserOptions::SetOption('webdav', static::OPT_DESKTOP_DISK_INSTALL, true, false, $USER->getId());
	}

	public static function setDesktopDiskUninstalled()
	{
		global $USER;
		CUserOptions::SetOption('webdav', static::OPT_DESKTOP_DISK_INSTALL, false, false, $USER->getId());
	}

	/**
	 * Get numeric case for lang messages
	 * @param $number
	 * @param $once
	 * @param $multi21
	 * @param $multi2_4
	 * @param $multi5_20
	 * @return string
	 */
	public static function getNumericCase($number, $once, $multi21, $multi2_4, $multi5_20)
	{
		if ($number == 1)
		{
			return $once;
		}

		if ($number < 0)
		{
			$number = -$number;
		}

		$number %= 100;
		if ($number >= 5 && $number <= 20)
		{
			return $multi5_20;
		}

		$number %= 10;
		if ($number == 1)
		{
			return $multi21;
		}

		if ($number >= 2 && $number <= 4)
		{
			return $multi2_4;
		}

		return $multi5_20;
	}

	public static function clearByTag($tagName)
	{
		global $CACHE_MANAGER;
		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			$CACHE_MANAGER->ClearByTag($tagName);
		}
	}

	/**
	 * Append rights
	 * @param CIBlockRights $ibRights
	 * @param array                $appendRights
	 * @param array                $tasks
	 * @return array
	 */
	public static function appendRights(CIBlockRights $ibRights, array $appendRights, array $tasks)
	{
		$taskIdToLetter = array_flip($tasks);
		$letterToTaskId = $tasks;
		$newRights = array();
		$existsRights = $ibRights->GetRights();

		$appendRightsGroupCode = array();
		foreach ($appendRights as $k => $right)
		{
			$appendRightsGroupCode[$right['GROUP_CODE']] = $k;
		}
		unset($right);

		$i = 0;
		foreach ($existsRights as $existsRight)
		{
			//if exists right not in appendRights and not inherited. We save this specified right
			if(empty($appendRightsGroupCode[$existsRight['GROUP_CODE']]) && $existsRight['IS_INHERITED'] != 'Y')
			{
				$newRights[] = array(
					'GROUP_CODE' => $existsRight['GROUP_CODE'],
					'TASK_ID' => $existsRight['TASK_ID'],
				);
				continue;
			}
			//if exists right is inherited and not in appendRights
			elseif($existsRight['IS_INHERITED'] == 'Y' && empty($appendRightsGroupCode[$existsRight['GROUP_CODE']]))
			{
				continue;
			}
			else
			{
				//attempt to rewrite this rule
				$keyOfAppendRight = $appendRightsGroupCode[$existsRight['GROUP_CODE']];
				//if new right > exists right
				if($taskIdToLetter[$appendRights[$keyOfAppendRight]['TASK_ID']] > $taskIdToLetter[$existsRight['TASK_ID']])
				{
					$newRights[] = array(
						'GROUP_CODE' => $existsRight['GROUP_CODE'],
						'TASK_ID' => $appendRights[$keyOfAppendRight]['TASK_ID'],
					);
				}
				//if exists right is not inherited and not in appendRights
				elseif($existsRight['IS_INHERITED'] != 'Y')
				{
					$newRights[] = array(
						'GROUP_CODE' => $existsRight['GROUP_CODE'],
						'TASK_ID' => $existsRight['TASK_ID'],
					);
				}
				unset($appendRights[$keyOfAppendRight]);
			}
		}
		unset($existsRight);

		$newRights = array_merge($newRights, $appendRights);

		$returnRights = array();
		$i = 0;
		foreach ($newRights as $right)
		{
			$returnRights['n' . $i++] = $right;
		}
		unset($right);

		return $returnRights;
	}

	public static function removeRights(CIBlockRights $ibRights, array $removedRights, array $tasks)
	{
		$taskIdToLetter = array_flip($tasks);
		$letterToTaskId = $tasks;
		$newRights = array();
		$existsRights = $ibRights->GetRights();

		$removedRightsGroupCode = array();
		foreach ($removedRights as $k => $right)
		{
			$removedRightsGroupCode[$right['GROUP_CODE']] = $k;
		}
		unset($right);

		foreach ($existsRights as $existsRight)
		{
			if(!empty($removedRightsGroupCode[$existsRight['GROUP_CODE']]) && $existsRight['IS_INHERITED'] != 'Y')
			{
				$keyOfAppendRight = $removedRightsGroupCode[$existsRight['GROUP_CODE']];
				//if new right == exists right, remove this
				if($taskIdToLetter[$removedRights[$keyOfAppendRight]['TASK_ID']] == $taskIdToLetter[$existsRight['TASK_ID']])
				{
					continue;
				}
				else
				{
					$newRights[] = array(
						'GROUP_CODE' => $existsRight['GROUP_CODE'],
						'TASK_ID' => $existsRight['TASK_ID'],
					);
				}
				unset($removedRights[$keyOfAppendRight]);
			}
			else
			{
				$newRights[] = array(
					'GROUP_CODE' => $existsRight['GROUP_CODE'],
					'TASK_ID' => $existsRight['TASK_ID'],
				);
			}

		}
		unset($existsRight);

		$returnRights = array();
		$i = 0;
		foreach ($newRights as $right)
		{
			$returnRights['n' . $i++] = $right;
		}
		unset($right);

		return $returnRights;
	}

	/**
	 * Run event
	 * @param $eventName
	 * @param $data
	 */
	public static function runEvent($eventName, $data)
	{
		foreach(GetModuleEvents('webdav', $eventName, true) as $event)
		{
			ExecuteModuleEventEx($event, $data);
		}
	}

	public static function getUser($userId, $photo = false)
	{
		global $USER;
		if (is_object($USER) && intVal($userId) == $USER->GetId() && !$photo)
		{
			$user = array(
				'ID' => $USER->GetId(),
				'NAME' => $USER->GetFirstName(),
				'LAST_NAME' => $USER->GetLastName(),
				'SECOND_NAME' => $USER->GetParam('SECOND_NAME'),
				'LOGIN' => $USER->GetLogin(),
			);
		}
		else
		{
			$user = CUser::getByID(intval($userId))->Fetch();
		}
		return $user;
	}

	public static function getUserName($user)
	{
		if (!is_array($user) && intVal($user) > 0)
			$user = self::getUser($user);

		if(!$user || !is_array($user))
			return '';

		return CUser::formatName(self::$userNameTemplate, $user, self::$showLogin, false);
	}


	public static function getUserGender($gender)
	{
		if(is_array($gender) && !empty($gender['PERSONAL_GENDER']))
		{
			return $gender['PERSONAL_GENDER'] == 'F'? 'F' : 'M';
		}
		elseif(is_array($gender) && !empty($gender['GENDER']))
		{
			return $gender['GENDER'] == 'F'? 'F' : 'M';
		}
		elseif(is_string($gender))
		{
			return $gender == 'F'? 'F' : 'M';
		}

		return 'M';
	}

	public static function getUserGenderByCurrentUser()
	{
		static $gender = null;
		if($gender !== null)
		{
			return $gender;
		}

		global $USER;
		$userData = array();
		if($userId = $USER->getId())
		{
			$userData = CUser::GetByID($userId)->Fetch();
		}
		$gender = static::getUserGender($userData);

		return $gender;
	}

	public static function allowCreateDocByExtServiceGlobal()
	{
		return static::allowUseExtServiceGlobal() && (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite());
	}

	/**
	 * @return bool
	 */
	public static function allowUseExtServiceGlobal()
	{
		static $isAllow = null;
		if($isAllow === null)
		{
			$isAllow = 'Y' == COption::GetOptionString('webdav', static::OPT_ALLOW_EXT_DOC_SERVICES_GLOBAL, CWebDavIblock::resolveDefaultUseExtServices());
		}
		return $isAllow;
	}

	/**
	 * @return bool
	 */
	public static function allowUseExtServiceLocal()
	{
		static $isAllow = null;
		if($isAllow === null)
		{
			$isAllow = 'Y' == COption::GetOptionString('webdav', static::OPT_ALLOW_EXT_DOC_SERVICES_LOCAL, CWebDavIblock::resolveDefaultUseExtServices());
		}
		return $isAllow;
	}

	/**
	 * @param      $filePath
	 * @param      $fileSize
	 * @param bool $checkFileSize
	 * @param null $service
	 * @return bool
	 */
	public static function allowPreviewFile($filePath, $fileSize, $checkFileSize = true, $service = null)
	{
		return
			(!$checkFileSize || $fileSize < CWebDavExtLinks::$maxSizeForView) &&
			(in_array(ltrim($filePath, '.'), CWebDavExtLinks::$allowedExtensionsGoogleViewer) ||
			in_array(ltrim(GetFileExtension($filePath), '.'), CWebDavExtLinks::$allowedExtensionsGoogleViewer));
	}

	/**
	 * @return bool
	 */
	public static function allowAutoconnectShareGroupFolder()
	{
		static $isAllow = null;
		if($isAllow === null)
		{
			$isAllow = 'Y' == COption::GetOptionString('webdav', static::OPT_ALLOW_AUTOCONNECT_SHARE_GROUP_FOLDER, 'Y');
		}
		return $isAllow;
	}

	/**
	 * @param string $str
	 * @return string
	*/
	public static function urlEncode($str)
	{
		global $APPLICATION;
		$strEncodedURL = '';
		$arUrlComponents = preg_split("#(://|/|\\?|=|&)#", $str, -1, PREG_SPLIT_DELIM_CAPTURE);
		foreach($arUrlComponents as $i => $part_of_url)
		{
			if((intval($i) % 2) == 1)
				$strEncodedURL .= (string)$part_of_url;
			else
				$strEncodedURL .= urlencode($APPLICATION->ConvertCharset((string)$part_of_url, LANG_CHARSET, 'UTF-8'));
		}
		return $strEncodedURL;
	}

	/**
	 * Add (1), (2), etc. if name non unique in target dir
	 * @param $name
	 * @param $iblockId
	 * @param $sectionId
	 * @return string
	 */
	public static function regenerateNameIfNonUnique($name, $iblockId, $sectionId)
	{
		$mainPartName = $name;
		$newName = $mainPartName;
		$count = 0;

		if(self::isMetaName($newName))
		{
			$count++;
			if(strstr($mainPartName, '.', true))
			{
				$newName = strstr($mainPartName, '.', true) . " ({$count})" . strstr($mainPartName, '.');
			}
			else
			{
				$newName = $mainPartName . " ({$count})";
			}
		}

		while(!CWebDavIblock::sCheckUniqueName($iblockId, $sectionId, '', $newName, $res))
		{
			$count++;
			if(strstr($mainPartName, '.', true))
			{
				$newName = strstr($mainPartName, '.', true) . " ({$count})" . strstr($mainPartName, '.');
			}
			else
			{
				$newName = $mainPartName . " ({$count})";
			}
		}

		return $newName;
	}

	private static function isMetaName($potentialName)
	{
		$metaData = CWebDavIblock::getFoldersMetaData();
		foreach ($metaData as $meta)
		{
			if((isset($meta['name']) && $potentialName == $meta['name']) || (isset($meta['alias']) && $potentialName == $meta['alias']))
			{
				return true;
			}
		}
		unset($meta);

		return false;
	}

	public static function isIntranetUser($userId)
	{
		if(!CModule::IncludeModule('intranet'))
		{
			return false;
		}
		$o = "ID";
		$b = '';
		$queryUser = CUser::GetList(
			$o,
			$b,
			array(
				"ID_EQUAL_EXACT" => $userId,
			),
			array(
				"FIELDS" => array("ID"),
				"SELECT" => array("UF_DEPARTMENT"),
			)
		);
		if ($user = $queryUser->Fetch())
		{
			if (intval($user["UF_DEPARTMENT"][0]) > 0)
			{
				return true;
			}
		}

		return false;
	}

}