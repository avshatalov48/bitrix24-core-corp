<?

includeModuleLangFile(__FILE__);

class CDavAccount
{
	private static $accountsCache = array("groups" => array(), "users" => array());
	private static $accountsCacheMap = array();

	public static function GetAccountByName($name)
	{
		if ($name == '')
		{
			throw new Exception("name");
		}

		$arResult = null;

		if (!strncasecmp("group-", $name, 6) && CModule::IncludeModule("socialnetwork"))
		{
			$groupId = (int)mb_substr($name, 6);

			if (array_key_exists($groupId, self::$accountsCache["groups"]))
			{
				return self::$accountsCache["groups"][$groupId];
			}

			$dbGroup = CSocNetGroup::GetList(array(), array("ID" => $groupId, "ACTIVE" => "Y"), false, false, array("ID", "SITE_ID", "NAME", "OWNER_ID", "OWNER_EMAIL"));
			if ($arGroup = $dbGroup->Fetch())
			{
				$arResult = self::ExtractAccountFromGroup($arGroup);
				self::$accountsCache["groups"][$arGroup["ID"]] = $arResult;
			}

			return $arResult;
		}

		if (array_key_exists($name, self::$accountsCacheMap))
		{
			return self::$accountsCache["users"][self::$accountsCacheMap[$name]];
		}


		$dbUsers = \Bitrix\Main\UserTable::getList([
			'filter' => ["=LOGIN" => $name, "=ACTIVE" => "Y", "!=EXTERNAL_AUTH_ID" => "email"],
			'select' => ["ID", "NAME", "EMAIL", "LAST_NAME", "LOGIN"]

		]);
		if ($arUser = $dbUsers->fetch())
		{
			$arResult = self::ExtractAccountFromUser($arUser);
			self::$accountsCache["users"][$arUser["ID"]] = $arResult;
			self::$accountsCacheMap[$name] = $arUser["ID"];
		}

		return $arResult;
	}

	private static function ExtractAccountFromGroup($arGroup)
	{
		return array(
			"ID" => $arGroup["ID"],
			"TYPE" => "group",
			"CODE" => "group-".$arGroup["ID"],
			"SITE_ID" => $arGroup["SITE_ID"],
			"NAME" => \Bitrix\Main\Text\Emoji::decode($arGroup["NAME"]),
			"EMAIL" => $arGroup["OWNER_EMAIL"],
		);
	}

	private static function ExtractAccountFromUser($arUser)
	{
		return [
			"ID" => $arUser["ID"],
			"TYPE" => "user",
			"CODE" => $arUser["LOGIN"],
			"NAME" => self::FormatUserName($arUser),
			"EMAIL" => $arUser["EMAIL"],
			"FIRST_NAME" => $arUser["NAME"],
			"LAST_NAME" => $arUser["LAST_NAME"],
		];
	}

	private static function FormatUserName($arUser)
	{
		$r = $arUser["NAME"];
		if ($r <> '' && $arUser["LAST_NAME"] <> '')
		{
			$r .= " ";
		}
		$r .= $arUser["LAST_NAME"];

		if ($r == '')
		{
			$r = $arUser["LOGIN"];
		}

		return $r;
	}

	public static function GetAccountById($account)
	{
		if (!is_array($account) || count($account) != 2)
		{
			throw new Exception("account");
		}

		$arResult = null;

		if ($account[0] === "group")
		{
			if (CModule::IncludeModule("socialnetwork"))
			{
				if (array_key_exists($account[1], self::$accountsCache["groups"]))
				{
					return self::$accountsCache["groups"][$account[1]];
				}

				$dbGroup = CSocNetGroup::GetList(array(), array("ID" => $account[1], "ACTIVE" => "Y"));
				if ($arGroup = $dbGroup->Fetch())
				{
					$arResult = self::ExtractAccountFromGroup($arGroup);
					self::$accountsCache["groups"][$arGroup["ID"]] = $arResult;
				}

				return $arResult;
			}
		}

		if (array_key_exists($account[1], self::$accountsCache["users"]))
		{
			return self::$accountsCache["users"][$account[1]];
		}

		$params = array(
			'filter' => array("ID" => $account[1], "=ACTIVE" => "Y"),
			'select' => array("ID", "NAME", "EMAIL", "LAST_NAME", "LOGIN")
		);
		$dbUsers = \Bitrix\Main\UserTable::getList($params);
		if ($arUser = $dbUsers->Fetch())
		{
			$arResult = self::ExtractAccountFromUser($arUser);
			self::$accountsCache["users"][$arUser["ID"]] = $arResult;
			self::$accountsCacheMap[$arUser["LOGIN"]] = $arUser["ID"];
		}

		return $arResult;
	}

	public static function GetAccountsList($type, $siteId, $arOrder = array(), $arFilter = array())
	{
		$arResult = array();
		$isExtranet = (CModule::IncludeModule('extranet') && (CExtranet::IsExtranetSite($siteId) || !CExtranet::IsIntranetUser($siteId)));

		if ($type === "group")
		{
			if (\Bitrix\Main\Loader::includeModule("socialnetwork"))
			{
				$arFilter = array_merge($arFilter, array("ACTIVE" => "Y", "VISIBLE" => "Y"));
				if ($isExtranet)
				{
					$arFilter['SITE_ID'] = $siteId;
				}

				$dbGroup = CSocNetGroup::GetList($arOrder, $arFilter);
				if ($arGroup = $dbGroup->Fetch())
				{
					$arResult[] = self::ExtractAccountFromGroup($arGroup);
				}
			}

			return $arResult;
		}

		if ($isExtranet)
		{
			$extranet_site = isModuleInstalled('extranet')
				? COption::getOptionString('extranet', 'extranet_site')
				: (isModuleInstalled('bitrix24') ? 'ex' : false);
			$arFilter['XML_ID'] = [
				'feed-'.$extranet_site,
				'task-'.$extranet_site,
			];
		}
		$arFilter = array_merge($arFilter, ["=ACTIVE" => "Y"]);

		$dbUsers = \Bitrix\Main\UserTable::getList(array(
			'filter' => $arFilter,
			'select' => array("ID", "NAME", "EMAIL", "LAST_NAME", "LOGIN"),
			'order' => array(
				'ID' => 'desc'
			)
		));
		while ($arUser = $dbUsers->fetch())
		{
			$arResult[] = self::ExtractAccountFromUser($arUser);
		}

		return $arResult;
	}


	private static function GetAddressbookExtranetUserFilter($siteId, $arFilter = array())
	{
		if (CModule::IncludeModule('extranet') && (CExtranet::IsExtranetSite($siteId) || !CExtranet::IsIntranetUser($siteId)))
		{
			if (!CExtranet::IsExtranetAdmin())
			{
				if (array_key_exists('ID', $arFilter) || empty($arFilter['XML_ID']))
				{
					$arIDs = array_merge(CExtranet::GetMyGroupsUsers($siteId), CExtranet::GetPublicUsers());

					if (array_key_exists("ID", $arFilter))
					{
						$arIDs1 = $arFilter["ID"];

						$arIDs = array_intersect($arIDs1, $arIDs);
					}
					else
					{
						$extranet_site = isModuleInstalled('extranet')
							? COption::getOptionString('extranet', 'extranet_site')
							: (isModuleInstalled('bitrix24') ? 'ex' : false);
						$arFilter['XML_ID'] = [
							'feed-'.$extranet_site,
							'task-'.$extranet_site,
						];
					}

					if (count($arIDs) <= 0)
					{
						$arFilter['ID'] = 0;
					}
				}
			}
		}
		else
		{
			$arFilter['!UF_DEPARTMENT'] = false;
		}

		return $arFilter;
	}

	public static function getAddressbookModificationLabel($collectionId)
	{
		list($siteId) = $collectionId;

		$arFilter = self::GetAddressbookExtranetUserFilter($siteId);

		if (!empty($arFilter['XML_ID']))
		{
			unset($arFilter['XML_ID']);
		}

		$dbUsers = \Bitrix\Main\UserTable::getList(array(
			'filter' => $arFilter,
			'select' => array('TIMESTAMP_X'),
			'order' => array(
				'TIMESTAMP_X' => 'desc'
			),
			'limit' => 1
		));
		if ($arUser = $dbUsers->fetch())
		{
			return $arUser["TIMESTAMP_X"];
		}
		return "";
	}

	public static function GetAddressbookContactsList($collectionId, $arFilter = array())
	{
		[$siteId] = $collectionId;
		$arFilter = self::GetAddressbookExtranetUserFilter($siteId, $arFilter);
		$arFilter["ACTIVE"] = "Y";
		$arResult = array();

		$canCache  = false;
		$fromCache = false;
		if(count($arFilter) == 2 && $arFilter['!UF_DEPARTMENT'] === false)
		{
			$canCache = true;

			$obDavCache = new CPHPCache;
			$cache_id = 'kp_dav_address_book';
			$cache_dir = '/dav/address_book';

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$cache_ttl = 2592000;
			}
			else
			{
				$cache_ttl = 1200;
			}

			if($obDavCache->InitCache($cache_ttl, $cache_id, $cache_dir))
			{
				$fromCache = true;
				$arResult = $obDavCache->GetVars();
			}
		}

		if (!$fromCache)
		{
			$xmlIds = array();
			if (!empty($arFilter['XML_ID']))
			{
				$xmlIds = (array)$arFilter['XML_ID'];
			}

			if (empty($xmlIds) || !empty($arFilter['ID']))
			{
				unset($arFilter['XML_ID']);
				$params = array(
					'filter' => $arFilter,
					'select' => array(
						'ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EMAIL', 'PERSONAL_BIRTHDAY', 'PERSONAL_PHOTO',
						'WORK_PHONE', 'PERSONAL_MOBILE', 'PERSONAL_PHONE', 'WORK_COMPANY', 'WORK_POSITION',
						'WORK_WWW', 'PERSONAL_WWW', 'PERSONAL_STREET', 'PERSONAL_CITY', 'PERSONAL_STATE',
						'PERSONAL_ZIP', 'PERSONAL_COUNTRY', 'WORK_STREET', 'WORK_CITY', 'WORK_STATE',
						'WORK_ZIP', 'WORK_COUNTRY', 'TIMESTAMP_X', 'UF_DEPARTMENT', 'UF_PHONE_INNER'
					)
				);
				$dbUsers = \Bitrix\Main\UserTable::getList($params);
				while ($arUser = $dbUsers->fetch())
				{
					$arUser['UF_DEPARTMENT'] = self::getDepartmentsNames($arUser['UF_DEPARTMENT']);
					$arResult[] = $arUser;
				}
			}

			if ($canCache)
			{
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					global $CACHE_MANAGER;
					$CACHE_MANAGER->startTagCache($cache_dir);
					$CACHE_MANAGER->registerTag("USER_CARD");
					$CACHE_MANAGER->endTagCache();
				}

				if ($obDavCache->startDataCache())
				{
					$obDavCache->endDataCache($arResult);
				}
			}
		}

		global $USER;
		if ($USER->isAuthorized() and !empty($xmlIds) || empty($arFilter['ID']) and CModule::includeModule('mail'))
		{
			$timestampX   = self::getAddressbookModificationLabel($collectionId);
			$extranetSite = isModuleInstalled('extranet') ? COption::getOptionString('extranet', 'extranet_site') : (isModuleInstalled('bitrix24') ? 'ex' : false);
			$extranetUser = (CModule::IncludeModule("extranet") && !CExtranet::IsIntranetUser());

			$rsSite = CSite::getList('', '', array('LID' => $siteId));
			while ($arSite = $rsSite->fetch())
			{
				if (
					(!$extranetUser && $extranetSite && $arSite['LID'] == $extranetSite)
					|| ($extranetUser && $extranetSite && $arSite['LID'] != $extranetSite)
				)
					continue;

				$server_name = $arSite['SERVER_NAME'] ?: COption::getOptionString('main', 'server_name', '');

				$xmlId = 'feed-'.$arSite['LID'];
				$siteName = !empty($arSite['SITE_NAME'])? $arSite['SITE_NAME'] : mb_strtoupper($arSite['LID']);
				if (empty($xmlIds) || in_array($xmlId, $xmlIds) and isModuleInstalled('blog'))
				{
					$forwardToPost = Bitrix\Mail\User::getForwardTo($arSite['LID'], $USER->getId(), 'BLOG_POST');
					$arResult[] = array(
						'ID'             => $xmlId,
						'LAST_NAME'      => getMessage('DAV_BLOG_POST_CONTACT_NAME') . '(' .  $siteName . ')',
						'EMAIL'          => reset($forwardToPost),
						'WORK_WWW'       => $server_name,
						'TIMESTAMP_X'    => $timestampX,
						'PERSONAL_PHOTO' => array('src' => '/bitrix/modules/dav/images/addressbook/feed.png')
					);
				}

				$xmlId = 'task-'.$arSite['LID'];
				if (empty($xmlIds) || in_array($xmlId, $xmlIds) and isModuleInstalled('tasks'))
				{
					$forwardToTask = Bitrix\Mail\User::getForwardTo($arSite['LID'], $USER->getId(), 'TASKS_TASK');
					$arResult[] = array(
						'ID'             => $xmlId,
						'LAST_NAME'      => getMessage('DAV_TASK_CONTACT_NAME') . '(' .  $siteName . ')',
						'EMAIL'          => reset($forwardToTask),
						'WORK_WWW'       => $server_name,
						'TIMESTAMP_X'    => $timestampX,
						'PERSONAL_PHOTO' => array('src' => '/bitrix/modules/dav/images/addressbook/task.png')
					);
				}
			}
		}

		return $arResult;
	}

	/**
	 * @param $departmentIds
	 * @return array
	 */
	private static function getDepartmentsNames($departmentIds)
	{
		static $cachedDepartments = array();
		$notCachedDepartmentIds = array_diff($departmentIds, array_keys($cachedDepartments));

		if (!empty($notCachedDepartmentIds))
		{
			$departmentsResult = CIBlockSection::GetList(
				array(),
				array('ID' => $notCachedDepartmentIds),
				false,
				array('ID', 'NAME')
			);
			while ($department = $departmentsResult->Fetch())
			{
				$cachedDepartments[''. $department['ID'] . ''] = $department;
			}
		}

		$departmentNames = array();
		foreach ($departmentIds as $id)
		{
			$departmentNames[] = $cachedDepartments[$id];
		}

		return $departmentNames;

	}
}
?>
