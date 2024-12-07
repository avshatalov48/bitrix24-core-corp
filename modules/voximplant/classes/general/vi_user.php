<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Voximplant as VI;
use Bitrix\Voximplant\Security\Helper;
use Bitrix\Voximplant\Security\Permissions;

class CVoxImplantUser
{
	private $user_name = null;
	private $user_password = null;
	private $error = null;

	const MODE_USER = 'USER';
	const MODE_PHONE = 'PHONE';
	const MODE_SIP = 'SIP';

	public static $cacheTtl = 86400;
	public static $cacheTag = 'vi_user_out_line_';
	public static $cacheTagAllowedLines = 'vi_user_allowed_lines_';
	public static $cacheTable = 'b_voximplant_user';

	function __construct()
	{
		$this->error = new CVoxImplantError(null, '', '');
	}

	public function GetUser($userId, $getPhoneAccess = false, $skipUpdateAccount = false)
	{
		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		$ViHttp = new CVoxImplantHttp();
		$result = $ViHttp->GetUser($userId, $getPhoneAccess);
		if (!$result || $ViHttp->GetError()->error)
		{
			$this->error = new CVoxImplantError(__METHOD__, $ViHttp->GetError()->code, $ViHttp->GetError()->msg);
			return false;
		}

		if (!$skipUpdateAccount)
		{
			$ViAccount = new CVoxImplantAccount();
			$ViAccount->SetAccountName($result->account_name);
			$ViAccount->SetAccountBalance($result->account_balance);
			$ViAccount->SetAccountCurrency($result->account_currency);
		}

		return $result;
	}

	public function GetUsers($userId = Array(), $getOneUser = false, $skipUpdateAccount = false)
	{
		if (!is_array($userId))
			$userId = Array($userId);

		foreach($userId as $key => $value)
			$userId[$key] = intval($value);

		$ViHttp = new CVoxImplantHttp();
		$result = $ViHttp->GetUsers($userId, !$getOneUser);
		if (!$result || $ViHttp->GetError()->error)
		{
			$this->error = new CVoxImplantError(__METHOD__, $ViHttp->GetError()->code, $ViHttp->GetError()->msg);
			return false;
		}

		if (!$skipUpdateAccount)
		{
			$ViAccount = new CVoxImplantAccount();
			$ViAccount->SetAccountName($result->account_name);
			$ViAccount->SetAccountBalance($result->account_balance);
			$ViAccount->SetAccountCurrency($result->account_currency);
		}

		return $result;
	}

	public function UpdateUserPassword($userId, $mode = self::MODE_USER, $password = false)
	{
		if ($password)
		{
			preg_match("/^[\\x20-\\x7e]{3,32}$/D", $password, $matches);
			if (empty($matches))
			{
				$this->error = new CVoxImplantError(__METHOD__, 'PASSWORD_INCORRECT', GetMessage('VI_USER_PASS_ERROR'));
				return false;
			}
		}

		$ViHttp = new CVoxImplantHttp();
		$result = $ViHttp->UpdateUserPassword($userId, $mode, $password);
		if (!$result || $ViHttp->GetError()->error)
		{
			if ($ViHttp->GetError()->code == 'USER_NOT_FOUND')
			{
				$this->ClearUserInfo($userId);
			}

			$this->error = new CVoxImplantError(__METHOD__, $ViHttp->GetError()->code, $ViHttp->GetError()->msg);
			return false;
		}

		global $USER_FIELD_MANAGER;
		if ($mode == self::MODE_USER)
		{
			$USER_FIELD_MANAGER->Update("USER", $userId, Array('UF_VI_PASSWORD' => $result->PASSWORD));
		}
		else if ($mode == self::MODE_PHONE)
		{
			$USER_FIELD_MANAGER->Update("USER", $userId, Array('UF_VI_PHONE_PASSWORD' => $result->PASSWORD));
		}

		return Array('PASSWORD' => $result->PASSWORD);
	}

	public static function GetCallByPhone($userId)
	{
		$userId = intval($userId);
		if(!$userId)
			return false;

		if (!self::GetPhoneActive($userId))
			return false;

		return CUserOptions::GetOption('voximplant', 'call_by_phone', true, $userId);
	}

	public function SetCallByPhone($userId, $active = true)
	{
		$userId = intval($userId);
		if(!$userId)
			return false;

		if ($active)
		{
			$arUserInfo = $this->GetUserInfo($userId);
			if (!$arUserInfo['phone_enable'])
			{
				$this->error = new CVoxImplantError(__METHOD__, 'PHONE_NOT_CONNECTED', 'Phone is not connected');
				return false;
			}
		}

		CUserOptions::SetOption('voximplant', 'call_by_phone', ($active? true: false), false, $userId);

		return true;
	}

	public static function GetPhoneActive($userId)
	{
		return CUserOptions::GetOption('voximplant', 'phone_device_active', false, $userId);
	}
	public function SetPhoneActive($userId, $active = false)
	{
		$userId = intval($userId);
		if(!$userId)
			return false;

		CUserOptions::SetOption('voximplant', 'phone_device_active', ($active? true: false), false, $userId);

		global $USER, $CACHE_MANAGER;
		$USER->Update($userId, Array('UF_VI_PHONE' => $active? 'Y': 'N'));

		if ($active)
		{
			$arUserInfo = $this->GetUserInfo($userId);
			if (!$arUserInfo['phone_enable'])
			{
				$USER->Update($userId, Array('UF_VI_PHONE' => 'N'));
				$CACHE_MANAGER->ClearByTag("USER_NAME_".$userId);
				CUserOptions::SetOption('voximplant', 'phone_device_active', false, false, $userId);
				return false;
			}
		}

		$CACHE_MANAGER->ClearByTag("USER_NAME_".$userId);

		if (CModule::IncludeModule('pull') && CPullOptions::GetQueueServerStatus())
		{
			\Bitrix\Pull\Event::add($userId,
				Array(
					'module_id' => 'voximplant',
					'command' => 'phoneDeviceActive',
					'params' => Array('active' => $active? 'Y': 'N')
				)
			);
		}

		return true;
	}

	public function GetOnlineUsers()
	{
		$ViHttp = new CVoxImplantHttp();
		$result = $ViHttp->GetOnlineUsers();
		if (!$result || $ViHttp->GetError()->error)
		{
			$this->error = new CVoxImplantError(__METHOD__, $ViHttp->GetError()->code, $ViHttp->GetError()->msg);
			return false;
		}

		return $result->result;
	}

	public function ClearUserInfo($userId)
	{
		$userId = intval($userId);
		if ($userId <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'USER_ID_NULL', 'UserId is not correct');
			return false;
		}

		global $USER;
		$USER->Update($userId, Array('UF_VI_PASSWORD' => '', 'UF_VI_PHONE_PASSWORD' => ''));

		return true;
	}

	public function SetUserPhone($userId, $number)
	{
		$userId = intval($userId);
		if ($userId <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'USER_ID_NULL', 'UserId is not correct');
			return false;
		}
		if ($number != CVoxImplantConfig::LINK_BASE_NUMBER)
		{
			$numbers = CVoxImplantConfig::GetPortalNumbers(true, true);
			if (!isset($numbers[$number]))
			{
				$number = '';
			}
		}
		global $USER_FIELD_MANAGER;
		$USER_FIELD_MANAGER->Update("USER", $userId, Array('UF_VI_BACKPHONE' => $number));

		VI\Integration\Pull::sendDefaultLineId($userId, ($number ?: CVoxImplantConfig::GetPortalNumber()));

		return true;
	}

	/**
	 * @param $userId
	 * @param bool $autoRegister
	 * @return \Bitrix\Main\Result
	 */
	public function getAuthorizationInfo($userId, $autoRegister = false)
	{
		$result = new \Bitrix\Main\Result();
		$userId = intval($userId);
		if ($userId <= 0)
		{
			$result->addError(new \Bitrix\Main\Error('userId is empty'));
			return $result;
		}

		if(!\Bitrix\Voximplant\Integration\Bitrix24::isEmailConfirmed())
		{
			$result->addError(new \Bitrix\Main\Error(GetMessage('VI_ERROR_EMAIL_NOT_CONFIRMED_2')));
			return $result;
		}

		$arUser = \Bitrix\Main\UserTable::getRow(array(
			'select' => array('UF_VI_PASSWORD', 'UF_DEPARTMENT'),
			'filter' => array('=ID' => $userId)
		));
		if(!$arUser)
		{
			$result->addError(new \Bitrix\Main\Error(GetMessage('VI_ERROR_USER_NOT_FOUND')));
			return $result;
		}

		if($arUser['UF_VI_PASSWORD'] == '')
		{
			if(!$autoRegister)
			{
				$result->addError(new \Bitrix\Main\Error(GetMessage('VI_ERROR_USER_NOT_REGISTERED')));
				return $result;
			}
			$registerResult = $this->GetUser($userId);
			if (!$registerResult || $this->GetError()->error)
			{
				$result->addError(new \Bitrix\Main\Error($this->GetError()->msg, $this->GetError()->code));
				return $result;
			}

			$arUser['UF_VI_PASSWORD'] = $registerResult->result->user_password;
			global $USER_FIELD_MANAGER;
			$USER_FIELD_MANAGER->Update("USER", $userId, Array('UF_VI_PASSWORD' => $arUser['UF_VI_PASSWORD']));
		}
		$viAccount = new CVoxImplantAccount();
		$callServer = $viAccount->GetCallServer();

		if(!$callServer)
		{
			return $result->addError(new \Bitrix\Main\Error(GetMessage('VI_ERROR_COULD_NOT_CREATE_ACCOUNT')));
		}

		$result->setData(array(
			'server' => str_replace('voximplant.com', 'bitrixphone.com', $viAccount->GetCallServer()),
			'login' => 'user'.$userId,
			'password' => $arUser['UF_VI_PASSWORD']
		));

		return $result;
	}

	public function GetUserInfo($userId, $getPhoneAccess = false)
	{
		$userId = intval($userId);
		if ($userId <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'USER_ID_NULL', 'UserId is not correct');
			return false;
		}

		if(!\Bitrix\Voximplant\Integration\Bitrix24::isEmailConfirmed())
		{
			$this->error =  new CVoxImplantError(__METHOD__, 'CONFIRMATION_ERROR', GetMessage('VI_ERROR_EMAIL_NOT_CONFIRMED'));
			return false;
		}

		$userPassword = '';
		$userBackphone = '';
		$phoneEnable = false;
		$phonePassword = '';

		$arUser = \Bitrix\Main\UserTable::getRow([
			'select' => ['UF_VI_PASSWORD', 'UF_VI_BACKPHONE', 'UF_VI_PHONE', 'UF_VI_PHONE_PASSWORD', 'UF_PHONE_INNER', 'UF_DEPARTMENT'],
			'filter' => [
				'=ID' => $userId,
				'=ACTIVE' => 'Y',
			]
		]);
		if ($arUser)
		{
			if ($arUser['UF_VI_PASSWORD'] <> '')
			{
				$userPassword = $arUser['UF_VI_PASSWORD'];
			}
			if ($arUser['UF_VI_PHONE_PASSWORD'] <> '')
			{
				$phonePassword = $arUser['UF_VI_PHONE_PASSWORD'];
			}
			$userInnerPhone = $arUser['UF_PHONE_INNER'];
			$userBackphone = $arUser['UF_VI_BACKPHONE'];
			if ($arUser['UF_VI_PHONE'] == 'Y')
			{
				$phoneEnable = true;
				$getPhoneAccess = true;
			}
			$arUser['IS_EXTRANET'] = self::IsExtranet($arUser);
			unset($arUser['UF_DEPARTMENT']);
		}
		else
		{
			$this->error = new CVoxImplantError(__METHOD__, 'USER_NOT_FOUND', 'User is not found!');
			return false;
		}

		if ($userPassword == '' || $getPhoneAccess && $phonePassword == '')
		{
			$result = $this->GetUser($userId, $getPhoneAccess, true);
			if (!$result || $this->GetError()->error)
			{
				$this->error = new CVoxImplantError(__METHOD__, $this->GetError()->code, $this->GetError()->msg);
				return false;
			}

			$userPassword = $result->result->user_password;
			$phonePassword =
				property_exists($result->result, 'phone_password')
					? $result->result->phone_password
					: null
			;

			global $USER_FIELD_MANAGER;
			$USER_FIELD_MANAGER->Update("USER", $userId, Array('UF_VI_PASSWORD' => $userPassword, 'UF_VI_PHONE_PASSWORD' => $phonePassword));
		}

		if ($userBackphone)
		{
			$portalPhones = CVoxImplantConfig::GetPortalNumbers(true, true);
			if (!isset($portalPhones[$userBackphone]))
			{
				$userBackphone = CVoxImplantConfig::GetPortalNumber();
			}
		}
		if ($userBackphone == '')
		{
			$userBackphone = CVoxImplantConfig::GetPortalNumber();
			if ($userBackphone == CVoxImplantConfig::LINK_BASE_NUMBER)
			{
				$userBackphone = '';
			}
		}

		$viAccount = new CVoxImplantAccount();

		return Array(
			'call_server' => str_replace('voximplant.com', 'bitrixphone.com', $viAccount->GetCallServer()),
			'user_login' => 'user'.$userId,
			'user_password' => $userPassword,
			'user_backphone' => $userBackphone,
			'user_innerphone' => $userInnerPhone,
			'phone_enable' => $phoneEnable,
			'phone_login' => $phonePassword? 'phone'.$userId: "",
			'phone_password' => $phonePassword,
			'user_extranet' => $arUser['IS_EXTRANET'],
		);
	}

	public static function getRemoteServer($update = false)
	{
		$viAccount = new CVoxImplantAccount();
		return str_replace('voximplant.com', 'bitrixphone.com', $viAccount->GetCallServer($update));
	}

	public static function getRemoteLogin($userId)
	{
		return 'user'.$userId;
	}

	public static function getUserOutgoingLine($userId)
	{
		$cache = \Bitrix\Main\Application::getInstance()->getManagedCache();
		if($cache->read(self::$cacheTtl, self::$cacheTag.$userId, self::$cacheTable))
		{
			return $cache->get(self::$cacheTag.$userId);
		}
		else
		{
			$outgoingLine = '';
			$userData = \Bitrix\Main\UserTable::getRow(array(
				'select' => array('ID', 'UF_VI_BACKPHONE'),
				'filter' => array('=ID' => $userId)
			));
			if ($userData)
			{
				$outgoingLine = $userData['UF_VI_BACKPHONE'];
				if ($outgoingLine)
				{
					$portalPhones = CVoxImplantConfig::GetPortalNumbers(true, true);
					if (!isset($portalPhones[$outgoingLine]))
					{
						$outgoingLine = CVoxImplantConfig::GetPortalNumber();
					}
				}
				if ($outgoingLine == '')
				{
					$outgoingLine = CVoxImplantConfig::GetPortalNumber();
				}
			}
			$cache->set(self::$cacheTag.$userId, $outgoingLine);
			return $outgoingLine;
		}

	}

	/**
	 * @deprecated
	 * Use Bitrix\Main\UserTable::getList instead
	 */
	public static function GetList($params)
	{
		$query = new \Bitrix\Main\Entity\Query(\Bitrix\Main\UserTable::getEntity());
		$query->registerRuntimeField(new \Bitrix\Main\Entity\ExpressionField(
			'IS_ONLINE_CUSTOM',
			"CASE WHEN LAST_ACTIVITY_DATE > ".self::GetLastActivityDateAgo()." THEN 'Y' ELSE 'N' END'"
		));

		if (isset($params['select']))
		{
			$query->setSelect($params['select']);
		}
		else
		{
			$query->addSelect('ID')->addSelect('IS_ONLINE_CUSTOM');
		}

		if (isset($params['filter']))
		{
			$query->setFilter($params['filter']);
		}

		if (isset($params['order']))
		{
			$query->setOrder($params['order']);
		}

		return $query->exec();
	}

	/**
	 * @deprecated
	 * Use \Bitrix\Main\UserTable::getSecondsForLimitOnline intead
	 * @return string
	 */
	public static function GetLastActivityDateAgo()
	{
		$lastActivityDate = 180;
		if (IsModuleInstalled('bitrix24'))
			$lastActivityDate = 1440;

		return Bitrix\Main\Application::getConnection()->getSqlHelper()->addSecondsToDateTime(-1 * $lastActivityDate);
	}

	public static function GetActiveStatusByTimeman($userId)
	{
		if ($userId <= 0)
			return false;

		if (CModule::IncludeModule('timeman'))
		{
			$tmUser = new CTimeManUser($userId);
			$tmSettings = $tmUser->GetSettings(Array('UF_TIMEMAN'));
			if (!$tmSettings['UF_TIMEMAN'])
			{
				$result = true;
			}
			else
			{
				if ($tmUser->getCurrentRecordStatus() === 'OPENED')
				{
					$result = true;
				}
				else
				{
					$result = false;
				}
			}
		}
		else
		{
			$result = true;
		}

		return $result;
	}

	public static function GetByPhone($phone)
	{
		$phone = CVoxImplantPhone::stripLetters($phone);

		if ($phone == '')
		{
			return false;
		}

		$row = VI\PhoneTable::getRow([
			'select' => ['USER_ID'],
			'filter' => ['=PHONE_NUMBER' => $phone, '=USER.ACTIVE' => 'Y']
		]);

		return ($row ? (int)$row['USER_ID'] : false);
	}

	public static function IsExtranet($arUser)
	{
		$result = false;
		if (IsModuleInstalled('extranet'))
		{
			if (array_key_exists('UF_DEPARTMENT', $arUser))
			{
				if ($arUser['UF_DEPARTMENT'] == "")
				{
					$result = true;
				}
				else if (is_array($arUser['UF_DEPARTMENT']) && empty($arUser['UF_DEPARTMENT']))
				{
					$result = true;
				}
				else if (is_array($arUser['UF_DEPARTMENT']) && count($arUser['UF_DEPARTMENT']) == 1 && $arUser['UF_DEPARTMENT'][0] == 0)
				{
					$result = true;
				}
			}
		}

		return $result;
	}

	public static function hasMobile($userId)
	{
		if(!\Bitrix\Main\Loader::includeModule('pull'))
			return false;

		$cursor = \Bitrix\Pull\Model\PushTable::getList(Array(
			'select' => Array('ID'),
			'filter' => Array('=USER_ID' => $userId),
		));

		return ($cursor->fetch() ? true : false);
	}

	public static function canModify($userId, Permissions $permissions)
	{
		$userId = (int)$userId;

		$allowedUserIds = Helper::getAllowedUserIds(
			$permissions->getUserId(),
			$permissions->getPermission(Permissions::ENTITY_USER, $permissions::ACTION_MODIFY)
		);
		if(is_array($allowedUserIds))
		{
			$result = in_array($userId, $allowedUserIds);
		}
		else
		{
			$result = true;
		}
		return $result;
	}

	public static function canUseLine($userId, $lineSearchId)
	{
		if(!\Bitrix\Voximplant\Limits::canSelectLine())
			return true;

		$allowedLines = self::getAllowedLines($userId);
		return in_array($lineSearchId, $allowedLines);
	}

	/**
	 * @param $userId
	 * @return array
	 */
	public static function getAllowedLines($userId)
	{
		if(!\Bitrix\Voximplant\Limits::canSelectLine())
			return array();

		$cacheManager = \Bitrix\Main\Application::getInstance()->getManagedCache();
		$cacheTag = static::$cacheTagAllowedLines.$userId;

		if ($cacheManager->read(static::$cacheTtl, $cacheTag, static::$cacheTable))
			return $cacheManager->get($cacheTag);

		$userAccessCodes = \CAccess::GetUserCodesArray($userId);
		if(!is_array($userAccessCodes) || empty($userAccessCodes))
			return array();

		$cursor = \Bitrix\Voximplant\Model\LineAccessTable::getList(array(
			'select' => array(
				new \Bitrix\Main\Entity\ExpressionField('DISTINCT_CONFIG_ID', 'DISTINCT %s', 'CONFIG_ID'),
				'PHONE_NAME' => 'CONFIG.PHONE_NAME',
				'PORTAL_MODE' => 'CONFIG.PORTAL_MODE',
				'SEARCH_ID' => 'CONFIG.SEARCH_ID',
				'RENTED_PHONE_NUMBER' => 'CONFIG.NUMBER.NUMBER',
				'GROUP_PHONE_NUMBER' => 'CONFIG.GROUP_NUMBER.NUMBER',
				'CALLER_ID_NUMBER' => 'CONFIG.CALLER_ID.NUMBER'
			),
			'filter' => array(
					'=ACCESS_CODE' => $userAccessCodes,
					'=CONFIG.CAN_BE_SELECTED' => 'Y',
				)
			)
		);

		$defaultLineId = static::getUserOutgoingLine($userId);
		$defaultLineFound = false;
		$result = array();
		while ($row = $cursor->fetch())
		{
			if ($row['PORTAL_MODE'] == CVoxImplantConfig::MODE_RENT)
			{
				$line = $row['RENTED_PHONE_NUMBER'];
			}
			else if ($row['PORTAL_MODE'] == CVoxImplantConfig::MODE_LINK)
			{
				$line = $row['CALLER_ID_NUMBER'];
			}
			else if ($row['PORTAL_MODE'] == CVoxImplantConfig::MODE_GROUP)
			{
				$line = $row['GROUP_PHONE_NUMBER'];
			}
			else
			{
				$line = $row['SEARCH_ID'];
			}

			if($line == $defaultLineId) $defaultLineFound = true;
			$result[] = $line;
		}
		if(!$defaultLineFound)
		{
			$result[] = $defaultLineId;
		}

		//rest apps and their lines are available unconditionally
		$restApps = VI\Rest\Helper::getExternalCallHandlers();
		$externalNumbers = array();
		$externalNumbersCursor = VI\Model\ExternalLineTable::getList(array(
			'filter' => array(
				'=REST_APP_ID' => array_keys($restApps)
			)
		));
		foreach ($externalNumbersCursor->getIterator() as $row)
		{
			$externalNumbers[$row['REST_APP_ID']][] = $row;
		}
		foreach ($restApps as $restAppId => $restAppName)
		{
			$prefixedRestAppId = CVoxImplantConfig::MODE_REST_APP . ':' . $restAppId;
			$result[] = $prefixedRestAppId;

			if($externalNumbers[$restAppId])
			{
				foreach ($externalNumbers[$restAppId] as $externalNumber)
				{
					$result[] = $externalNumber['NUMBER'];
				}
			}
		}

		$cacheManager->set($cacheTag, $result);
		return $result;
	}

	/**
	 * @return array
	 */
	public static function getOnlineUsersWithNotDefaultNumber()
	{
		$result = array();

		$cursor = \Bitrix\Main\UserTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'=UF_VI_BACKPHONE' => '',
				'=IS_REAL_USER' => 'Y',
				'=IS_ONLINE' => 'Y'
			),
			'order' => array(
				'ID' => 'asc'
			)
		));

		while($row = $cursor->fetch())
		{
			$result[] = $row['ID'];
		}

		return $result;
	}

	public static function clearCache($userId = 0)
	{
		$userId = (int)$userId;
		$cache = \Bitrix\Main\Application::getInstance()->getManagedCache();
		if($userId > 0)
		{
			$cache->clean(self::$cacheTag.$userId, self::$cacheTable);
			$cache->clean(self::$cacheTagAllowedLines.$userId, self::$cacheTable);
		}
		else
		{
			$cache->cleanDir(self::$cacheTable);
		}
	}

	public function GetError()
	{
		return $this->error;
	}
}