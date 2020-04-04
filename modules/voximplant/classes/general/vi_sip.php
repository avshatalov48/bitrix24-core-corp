<?
IncludeModuleLangFile(__FILE__);

use Bitrix\VoxImplant as VI;
use Bitrix\Main\Localization\Loc;

class CVoxImplantSip
{
	private $server = null;
	private $user_name = null;
	private $user_password = null;
	private $error = null;

	const CHECK_ADD = 'add';
	const CHECK_UPDATE = 'update';

	const TYPE_OFFICE = 'office';
	const TYPE_CLOUD = 'cloud';

	const REG_STATUS_SUCCESS = 'success';
	const REG_STATUS_ERROR = 'error';
	const REG_STATUS_IN_PROGRESS = 'in_progress';
	const REG_STATUS_WAIT = 'wait';

	const MAX_CLOUD_PBX = 10;

	function __construct()
	{
		$this->error = new CVoxImplantError(null, '', '');
	}

	public function Add($fields)
	{
		$arAdd = $this->PrepareFields($fields);
		if (!$arAdd)
			return false;

		if ($arAdd['TYPE'] == self::TYPE_CLOUD)
		{
			$countQuery = new \Bitrix\Main\Entity\Query(VI\SipTable::getEntity());
			$countQuery->addSelect(new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)'));
			$countQuery->setFilter(Array(
				'TYPE' => self::TYPE_CLOUD
			));
			$totalCount = $countQuery->setLimit(null)->setOffset(null)->exec()->fetch();

			if ($totalCount['CNT'] >= self::MAX_CLOUD_PBX)
			{
				$this->error = new CVoxImplantError(__METHOD__, 'MAX_CLOUD_PBX', GetMessage('VI_SIP_ADD_CLOUD_ERR', Array("#NUMBER#" => self::MAX_CLOUD_PBX)));

				return false;
			}
		}

		if (strlen($arAdd['PHONE_NAME']) > 0)
		{
			$orm = VI\ConfigTable::getList(Array(
				'filter' => Array('=PHONE_NAME' => $arAdd['PHONE_NAME'])
			));
			if ($orm->fetch())
			{
				$this->error = new CVoxImplantError(__METHOD__, 'TITLE_EXISTS', GetMessage('VI_SIP_TITLE_EXISTS'));

				return false;
			}
		}

		$melodyLang = ToUpper(LANGUAGE_ID);
		if($melodyLang === 'KZ')
		{
			$melodyLang = 'RU';
		}
		else if(!in_array($melodyLang, CVoxImplantConfig::GetMelodyLanguages()))
		{
			$melodyLang = 'EN';
		}

		$result = VI\ConfigTable::add(Array(
			'PORTAL_MODE' => 'SIP',
			'SEARCH_ID' => $arAdd['SEARCH_ID'],
			'PHONE_NAME' => trim($arAdd['PHONE_NAME']),
			'MELODY_LANG' => $melodyLang,
			'QUEUE_ID' => CVoxImplantMain::getDefaultGroupId(),
		));
		CVoxImplantUser::clearCache();
		if (!$result)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'TITLE_EXISTS', GetMessage('VI_SIP_TITLE_EXISTS'));

			return false;
		}

		$configId = $result->getId();

		if (CVoxImplantConfig::GetPortalNumber() == CVoxImplantConfig::LINK_BASE_NUMBER)
		{
			CVoxImplantConfig::SetPortalNumber($arAdd['SEARCH_ID']);
		}

		unset($arAdd['SEARCH_ID']);
		unset($arAdd['PHONE_NAME']);
		$arAdd['CONFIG_ID'] = $configId;
		VI\SipTable::add($arAdd);

		return $configId;
	}

	public function Update($configId, $fields)
	{
		$configId = intval($configId);
		if ($configId <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CONFIG_ID_NULL', GetMessage('VI_SIP_CONFIG_ID_NULL'));
			return false;
		}
		$arUpdate = $this->PrepareFields($fields, self::CHECK_UPDATE);
		if (!$arUpdate)
			return false;

		if (isset($arUpdate['SEARCH_ID']))
		{
			$orm = VI\ConfigTable::getList(Array(
				'filter'=>Array(
					'=SEARCH_ID' => $arUpdate['SEARCH_ID'],
					'!=ID' => $configId
				)
			));
			if ($orm->fetch())
			{
				$this->error = new CVoxImplantError(__METHOD__, 'SEARCH_ID_EXISTS', GetMessage('VI_SIP_SEARCH_ID_EXISTS'));
				return false;
			}
		}
		if (isset($arUpdate['PHONE_NAME']))
		{
			$orm = VI\ConfigTable::getList(Array(
				'filter'=>Array(
					'=PHONE_NAME' => $arUpdate['PHONE_NAME'],
					'!=ID' => $configId
				)
			));
			if ($orm->fetch())
			{
				$this->error = new CVoxImplantError(__METHOD__, 'TITLE_EXISTS', GetMessage('VI_SIP_TITLE_EXISTS'));
				return false;
			}
		}

		if (isset($arUpdate['SEARCH_ID']))
		{
			$result = \Bitrix\Voximplant\ConfigTable::getById($configId);
			$currentConfig = $result->fetch();
			if ($currentConfig['SEARCH_ID'] == CVoxImplantConfig::GetPortalNumber())
			{
				COption::SetOptionString("voximplant", "portal_number", $arUpdate['SEARCH_ID']);
			}

			VI\ConfigTable::update($configId, Array(
				'SEARCH_ID' => $arUpdate['SEARCH_ID'],
			));
		}
		if (isset($arUpdate['PHONE_NAME']))
		{
			VI\ConfigTable::update($configId, Array(
				'PHONE_NAME' => $arUpdate['PHONE_NAME'],
			));
		}

		unset($arUpdate['SEARCH_ID']);
		unset($arUpdate['PHONE_NAME']);
		unset($arUpdate['CONFIG_ID']);
		if (empty($arUpdate))
			return true;

		$orm = VI\SipTable::getList(Array(
			'filter'=>Array(
				'=CONFIG_ID' => $configId,
			)
		));
		$entity = $orm->fetch();
		if (!$entity)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CONFIG_NOT_FOUND', GetMessage('VI_SIP_CONFIG_NOT_FOUND'));
			return false;
		}

		VI\SipTable::update($entity['ID'], $arUpdate);

		if (isset($arUpdate['SERVER']) || isset($arUpdate['LOGIN']) || isset($arUpdate['PASSWORD']))
		{
			$orm = VI\SipTable::getById($entity['ID']);
			$sipConfig = $orm->fetch();

			if (
				$entity['SERVER'] != $sipConfig['SERVER'] ||
				$entity['LOGIN'] != $sipConfig['LOGIN'] ||
				$entity['PASSWORD'] != $sipConfig['PASSWORD'] ||
				$entity['AUTH_USER'] != $sipConfig['AUTH_USER'] ||
				$entity['OUTBOUND_PROXY'] != $sipConfig['OUTBOUND_PROXY'] ||
				$fields['NEED_UPDATE'] == 'Y'
			)
			{
				$this->UpdateSipRegistration(
					$sipConfig['REG_ID'],
					$sipConfig['SERVER'],
					$sipConfig['LOGIN'],
					$sipConfig['PASSWORD'],
					$sipConfig['AUTH_USER'],
					$sipConfig['OUTBOUND_PROXY']
				);
			}

		}

		return true;
	}

	public function Delete($configId)
	{
		$configId = intval($configId);
		if ($configId <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CONFIG_ID_NULL', GetMessage('VI_SIP_CONFIG_ID_NULL'));
			return false;
		}
		$orm = VI\SipTable::getList(Array(
			'filter'=>Array(
				'=CONFIG_ID' => $configId
			)
		));
		$element = $orm->fetch();
		if (!$element)
			return false;

		if (intval($element['REG_ID']) > 0 && !$this->DeleteSipRegistration($element['REG_ID']))
			return false;

		VI\SipTable::delete($element['ID']);
		CVoxImplantPhone::DeletePhoneConfig($configId);

		return true;
	}

	public function PrepareFields($fields, $type = self::CHECK_ADD)
	{
		$result = Array();
		$errors = Array();

		if (isset($fields['TITLE']))
		{
			$result['PHONE_NAME'] = trim($fields['TITLE']);
		}
		else if (isset($fields['PHONE_NAME']))
		{
			$result['PHONE_NAME'] = trim($fields['PHONE_NAME']);
		}

		if (!in_array($fields['TYPE'], Array(self::TYPE_OFFICE, self::TYPE_CLOUD)))
		{
			$errors[] = GetMessage('VI_SIP_TYPE_ERR');
		}
		else if ($type == self::CHECK_ADD)
		{
			$result['TYPE'] = $fields['TYPE'];
		}

		if (isset($fields['REG_ID']) && $fields['TYPE'] == self::TYPE_CLOUD)
		{
			$result['REG_ID'] = intval($fields['REG_ID']);
			$result['SEARCH_ID'] = 'reg'.$result['REG_ID'];
		}
		else if (isset($fields['SEARCH_ID']) && $type == self::CHECK_UPDATE)
		{
			$result['SEARCH_ID'] = trim($fields['SEARCH_ID']);
		}
		else if ($type == self::CHECK_ADD)
		{
			$result['SEARCH_ID'] = $fields['TYPE'] == self::TYPE_CLOUD? 'reg0': 'sip0';
		}

		if (isset($fields['SERVER']))
		{
			$result['SERVER'] = trim($fields['SERVER']);
			$result['SERVER'] = str_replace(Array('http://', 'https://'), '', $result['SERVER']);
			if (strlen($result['SERVER']) > 100)
				$errors[] = GetMessage('VI_SIP_SERVER_100');
			else if (strlen($result['SERVER']) <= 0)
				$errors[] = GetMessage('VI_SIP_SERVER_0');
		}
		else if ($type == self::CHECK_ADD)
		{
			$errors[] = GetMessage('VI_SIP_SERVER_0');
		}

		if (isset($fields['LOGIN']))
		{
			$result['LOGIN'] = trim($fields['LOGIN']);
			if (strlen($result['LOGIN']) > 100)
				$errors[] = GetMessage('VI_SIP_LOGIN_100');
			else if (strlen($result['LOGIN']) <= 0)
				$errors[] = GetMessage('VI_SIP_LOGIN_0');
		}
		else if ($type == self::CHECK_ADD)
		{
			$errors[] = GetMessage('VI_SIP_LOGIN_0');
		}

		if (isset($fields['PASSWORD']))
		{
			$result['PASSWORD'] = trim($fields['PASSWORD']);
			if (strlen($fields['PASSWORD']) > 100)
				$errors[] = GetMessage('VI_SIP_PASSWORD_100');
		}

		if ($fields['TYPE'] == self::TYPE_OFFICE)
		{
			if (isset($fields['INCOMING_SERVER']))
			{
				$result['INCOMING_SERVER'] = trim($fields['INCOMING_SERVER']);
				if (strlen($fields['INCOMING_SERVER']) > 100)
					$errors[] = GetMessage('VI_SIP_INC_SERVER_100');
				else if (strlen($fields['INCOMING_SERVER']) <= 0)
					$errors[] = GetMessage('VI_SIP_INC_SERVER_0');
			}

			if (isset($fields['INCOMING_LOGIN']))
			{
				$result['INCOMING_LOGIN'] = trim($fields['INCOMING_LOGIN']);
				if (strlen($fields['INCOMING_LOGIN']) > 100)
					$errors[] = GetMessage('VI_SIP_INC_LOGIN_100');
				else if (strlen($fields['INCOMING_LOGIN']) <= 0)
					$errors[] = GetMessage('VI_SIP_INC_LOGIN_0');

				$result['SEARCH_ID'] = $result['INCOMING_LOGIN'];
			}

			if (isset($fields['INCOMING_PASSWORD']))
			{
				$result['INCOMING_PASSWORD'] = trim($fields['INCOMING_PASSWORD']);
				if (strlen($result['INCOMING_PASSWORD']) > 100)
					$errors[] = GetMessage('VI_SIP_INC_PASSWORD_100');
			}
		}

		if (isset($fields['APP_ID']))
		{
			$result['APP_ID'] = trim($fields['APP_ID']);
		}

		if(isset($fields['AUTH_USER']))
		{
			$result['AUTH_USER'] = $fields['AUTH_USER'];
		}

		if(isset($fields['OUTBOUND_PROXY']))
		{
			$result['OUTBOUND_PROXY'] = $fields['OUTBOUND_PROXY'];
		}

		if (count($errors) > 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CHECK_FIELDS_ERROR', implode('<br> ', $errors));
			return false;
		}
		else
		{
			return $result;
		}
	}

	public function Get($configId, $params = Array())
	{
		$configId = intval($configId);
		if ($configId <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CONFIG_ID_NULL', GetMessage('VI_SIP_CONFIG_ID_NULL'));
			return false;
		}

		if (!is_array($params))
		{
			$params = Array();
		}

		$result = VI\SipTable::getList(Array(
			'select' => $params['WITH_TITLE']? Array('*', 'TITLE'): Array('*'),
			'filter' => Array('=CONFIG_ID' => $configId)
		));
		$row = $result->fetch();
		if (!$row)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CONFIG_NOT_FOUND', GetMessage('VI_SIP_CONFIG_NOT_FOUND'));
			return false;
		}

		if ($row['TYPE'] == self::TYPE_CLOUD)
		{
			if (intval($row['REG_ID']) <= 0)
			{
				$result = $this->CreateSipRegistration(
					$row['ID'],
					$row['CONFIG_ID'],
					$row['SERVER'],
					$row['LOGIN'],
					$row['PASSWORD'],
					$row['AUTH_USER'],
					$row['OUTBOUND_PROXY']
				);
				if ($result)
				{
					$row['REG_ID'] = $result->reg_id;
				}
				$row['REG_STATUS'] = $result? self::REG_STATUS_IN_PROGRESS: self::REG_STATUS_ERROR;
			}
			else
			{
				$row['REG_STATUS'] = self::REG_STATUS_WAIT;
			}
			unset($row['INCOMING_SERVER']);
			unset($row['INCOMING_LOGIN']);
			unset($row['INCOMING_PASSWORD']);
		}
		else
		{
			if (empty($row['INCOMING_SERVER']) && empty($row['INCOMING_LOGIN']) && empty($row['INCOMING_PASSWORD']))
			{
				$ViHttp = new CVoxImplantHttp();
				$result = $ViHttp->GetSipParams($configId);
				if ($result)
				{
					$row['INCOMING_SERVER'] = str_replace(Array('incoming.', '.voximplant.com'), Array('ip.', '.bitrixphone.com'), $result->server);
					$row['INCOMING_LOGIN'] = $result->user_name;
					$row['INCOMING_PASSWORD'] = $result->user_password;

					$this->Update($configId, Array(
						'TYPE' => self::TYPE_OFFICE,
						'INCOMING_SERVER' => $row['INCOMING_SERVER'],
						'INCOMING_LOGIN' => $row['INCOMING_LOGIN'],
						'INCOMING_PASSWORD' => $row['INCOMING_PASSWORD']
					));
				}
			}
			else
			{
				$row['INCOMING_SERVER'] = str_replace(Array('incoming.', '.voximplant.com'), Array('ip.', '.bitrixphone.com'), $row['INCOMING_SERVER']);
			}
			unset($row['REG_ID']);
		}

		unset($row['ID']);
		return $row;
	}

	private function CreateSipRegistration($sipId, $configId, $server, $login, $password = '', $authUser = '', $outboundProxy = '')
	{
		$viHttp = new CVoxImplantHttp();
		$result = $viHttp->CreateSipRegistration($server, $login, $password, $authUser, $outboundProxy);
		if (!$result)
			return false;

		VI\SipTable::update($sipId, Array('REG_ID' => $result->reg_id));

		$this->Update($configId, Array(
			'TYPE' => self::TYPE_CLOUD,
			'SEARCH_ID' => 'reg'.$result->reg_id
		));

		return $result;
	}

	private function UpdateSipRegistration($regId, $server, $login, $password = '', $authUser = '', $outboundProxy = '')
	{
		$viHttp = new CVoxImplantHttp();
		$result = $viHttp->UpdateSipRegistration($regId, $server, $login, $password, $authUser, $outboundProxy);
		if (!$result)
			return false;

		return true;
	}

	public function DeleteSipRegistration($regId)
	{
		$viHttp = new CVoxImplantHttp();
		$result = $viHttp->DeleteSipRegistration($regId);
		if (!$result)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'REG_ID_NOT_FOUND', GetMessage('VI_SIP_CONFIG_NOT_FOUND'));
			return false;
		}

		return true;
	}

	public function GetSipRegistrations($regId)
	{
		$viHttp = new CVoxImplantHttp();
		$result = $viHttp->GetSipRegistrations($regId);
		if (!$result)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'REG_ID_NOT_FOUND', GetMessage('VI_SIP_CONFIG_NOT_FOUND'));
			return false;
		}

		return $result;
	}

	public static function getConnectionDescription($connectionFields)
	{
		return Loc::getMessage("VI_SIP_DESCRIPTION", [
			"#SIP_SERVER#" => $connectionFields["SIP_SERVER"],
			"#SIP_LOGIN#" => $connectionFields["SIP_LOGIN"]
		]);
	}
	
	public static function isActive()
	{
		return CVoxImplantConfig::GetModeStatus(CVoxImplantConfig::MODE_SIP);
	}

	public function GetError()
	{
		return $this->error;
	}
}
?>
