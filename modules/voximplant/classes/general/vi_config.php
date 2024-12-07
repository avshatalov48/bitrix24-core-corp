<?php
IncludeModuleLangFile(__FILE__);

use Bitrix\Voximplant as VI;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\PhoneNumber;

class CVoxImplantConfig
{
	const MODE_LINK = 'LINK';
	const MODE_RENT = 'RENT';
	const MODE_SIP = 'SIP';
	const MODE_FAKE = 'FAKE';
	const MODE_GROUP = 'GROUP';
	const MODE_REST_APP = 'REST_APP';

	const INTERFACE_CHAT_ADD = 'ADD';
	const INTERFACE_CHAT_APPEND = 'APPEND';
	const INTERFACE_CHAT_NONE = 'NONE';

	const CRM_CREATE_NONE = 'none';
	const CRM_CREATE_LEAD = 'lead';

	const CRM_CREATE_CALL_TYPE_INCOMING = 'incoming';
	const CRM_CREATE_CALL_TYPE_OUTGOING = 'outgoing';
	const CRM_CREATE_CALL_TYPE_ALL = 'all';

	const QUEUE_TYPE_EVENLY = 'evenly';
	const QUEUE_TYPE_STRICTLY = 'strictly';
	const QUEUE_TYPE_ALL = 'all';

	const LINK_BASE_NUMBER = 'LINK_BASE_NUMBER';
	const FORWARD_LINE_DEFAULT = 'default';

	const GET_BY_SEARCH_ID = 'SEARCH_ID';
	const GET_BY_ID = 'ID';

	const WORKFLOW_START_IMMEDIATE = 'immediate';
	const WORKFLOW_START_DEFERRED = 'deferred';

	const BACKUP_NUMBER_COMMON = 'COMMON';
	const BACKUP_NUMBER_SPECIFIC = 'SPECIFIC';

	public static function SetPortalNumber($number)
	{
		$numbers = self::GetPortalNumbers(true, true);
		if (!(isset($numbers[$number]) || $number == CVoxImplantConfig::LINK_BASE_NUMBER))
		{
			return false;
		}
		COption::SetOptionString("voximplant", "portal_number", $number);
		self::clearUserCache($number, true);

		return true;
	}

	public static function GetPortalNumber()
	{
		$result = COption::GetOptionString("voximplant", "portal_number");
		$portalNumbers = self::GetPortalNumbers(true, true);
		if(!isset($portalNumbers[$result]))
		{
			$result = self::LINK_BASE_NUMBER;
		}

		return $result;
	}

	public static function SetPortalNumberByConfigId($configId)
	{
		$configId = intval($configId);
		if ($configId <= 0)
			return false;

		$orm = VI\ConfigTable::getList(Array(
			'filter'=>Array(
				'=ID' => $configId
			)
		));
		$element = $orm->fetch();
		if (!$element)
			return false;

		COption::SetOptionString("voximplant", "portal_number", $element['SEARCH_ID']);
		self::clearUserCache($element['SEARCH_ID'], true);

		return true;
	}

	public static function GetPortalNumbers($showBaseNumber = true, $showRestApps = false)
	{
		$lines = self::GetLines($showBaseNumber, $showRestApps);

		$result = array();
		foreach ($lines as $line)
		{
			$result[$line['LINE_NUMBER']] = htmlspecialcharsbx($line['FULL_NAME']);
		}

		return $result;
	}

	/**
	 * @param bool $showBaseNumber // not used anymore
	 * @param bool $showRestApps
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function GetLines($showBaseNumber = true, $showRestApps = false)
	{
		return static::GetLinesEx([
			'showRestApps' => $showRestApps
		]);
	}

	/**
	 * Returns known portal lines.
	 *
	 * @param array $params Parameters.
	 * <li> showRestApps bool Should the method return rest applications pseudo-lines. Default false.
	 * <li> showInboundOnly bool Should the method return inbound only lines (like SIP PBX lines, gathered from the diversion header).
	 * @return array
	 */
	public static function GetLinesEx(array $params = [])
	{
		$showRestApps = isset($params['showRestApps']) && $params['showRestApps'] === true;
		$showInboundOnly = isset($params['showInboundOnly']) && $params['showInboundOnly'] === true;

		static $cache = [];
		$cacheKey = ($showRestApps ? 'r' : '_') . ($showInboundOnly ? 'i' : '_');

		if (isset($cache[$cacheKey]))
		{
			return $cache[$cacheKey];
		}

		$cacheTtl = 86400; //1 day
		$result = Array();

		$cursor = VI\Model\NumberTable::getList([
			'select' => ['ID', 'NUMBER'],
			'cache' => [
				'ttl' => $cacheTtl
			]
		]);
		while ($row = $cursor->fetch())
		{
			$name = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($row['NUMBER'])->format(\Bitrix\Main\PhoneNumber\Format::INTERNATIONAL);
			$result[$row['NUMBER']] = array(
				'LINE_NUMBER' => $row['NUMBER'],
				'SHORT_NAME' => $name,
				'FULL_NAME' => $name,
				'TYPE' => static::MODE_RENT
			);
		}

		$res = VI\ConfigTable::getList([
			'select' => [
				'ID',
				'PORTAL_MODE',
				'SEARCH_ID',
				'PHONE_NAME',
				'RENTED_PHONE_NUMBER' => 'NUMBER.NUMBER',
				'CALLER_ID_PHONE_NUMBER' => 'CALLER_ID.NUMBER'
			],
			'filter' => [
				'=PORTAL_MODE' => [static::MODE_SIP, static::MODE_LINK]
			],
			'cache' => [
				'ttl' => $cacheTtl
			]
		]);
		while ($row = $res->fetch())
		{
			if ($row['SEARCH_ID'] == 'test')
				continue;

			switch ($row['PORTAL_MODE'])
			{
				case CVoxImplantConfig::MODE_SIP:
					$searchId = $row['SEARCH_ID'];
					$name = $row['PHONE_NAME'] ?: static::GetDefaultPhoneName($row);
					break;

				case CVoxImplantConfig::MODE_LINK:
					$searchId = $row['CALLER_ID_PHONE_NUMBER'];
					$name = PhoneNumber\Parser::getInstance()->parse($row['CALLER_ID_PHONE_NUMBER'])->format(PhoneNumber\Format::INTERNATIONAL);
					break;

				default:
					continue 2;
			}

			$result[$searchId] = array(
				'LINE_NUMBER' => $searchId,
				'SHORT_NAME' => $name,
				'FULL_NAME' => $name,
				'TYPE' => $row['PORTAL_MODE']
			);
		}

		$externalRestNumbers = array();
		$externalSipNumbers = array();
		if($showRestApps || $showInboundOnly)
		{
			$externalNumbersCursor = VI\Model\ExternalLineTable::getList(array(
				'select' => ['*', 'CONFIG_ID' => 'SIP.CONFIG_ID', 'SEARCH_ID' => 'SIP.CONFIG.SEARCH_ID'],
				'cache' => array(
					'ttl' => $cacheTtl
				)
			));

			foreach ($externalNumbersCursor->getIterator() as $row)
			{
				switch ($row['TYPE'])
				{
					case VI\Model\ExternalLineTable::TYPE_SIP:
						if($row['CONFIG_ID'] > 0)
						{
							$externalSipNumbers[] = $row;
						}
						break;
					case VI\Model\ExternalLineTable::TYPE_REST_APP:
						$externalRestNumbers[$row['REST_APP_ID']][] = $row;
						break;
				}
			}
		}

		if($showInboundOnly)
		{
			foreach ($externalSipNumbers as $externalNumber)
			{
				$formattedNumber = PhoneNumber\Parser::getInstance()->parse($externalNumber['NUMBER'])->format();
				$result[$externalNumber['NORMALIZED_NUMBER']] = array(
					'LINE_NUMBER' => $externalNumber['NORMALIZED_NUMBER'],
					'SHORT_NAME' => $formattedNumber,
					'FULL_NAME' => $formattedNumber,
					'TYPE' => 'SIP',
					'PARENT_ID' => $externalNumber['SEARCH_ID']
				) ;
			}
		}

		if($showRestApps)
		{
			$restApps = VI\Rest\Helper::getExternalCallHandlers();

			foreach ($restApps as $restAppId => $restAppName)
			{
				if($restAppName == '')
					$restAppName = Loc::getMessage('VI_CONFIG_NO_NAME');

				$prefixedRestAppId = CVoxImplantConfig::MODE_REST_APP . ':' . $restAppId;
				$result[$prefixedRestAppId] = array(
					'LINE_NUMBER' => $prefixedRestAppId,
					'SHORT_NAME' => GetMessage("VI_CONFIG_REST_APP").": ".$restAppName,
					'FULL_NAME' => GetMessage("VI_CONFIG_REST_APP").": ".$restAppName,
					'TYPE' => 'REST',
					'REST_APP_ID' => $restAppId
				);
				if($externalRestNumbers[$restAppId])
				{
					foreach ($externalRestNumbers[$restAppId] as $externalNumber)
					{
						$result[$externalNumber['NUMBER']] = array(
							'LINE_NUMBER' => $externalNumber['NUMBER'],
							'SHORT_NAME' => $externalNumber['NUMBER'],
							'FULL_NAME' => GetMessage("VI_CONFIG_REST_APP").": ".$restAppName. ": " . ($externalNumber['NAME'] ?  $externalNumber['NUMBER'] . " - " . $externalNumber['NAME'] : $externalNumber['NUMBER']),
							'TYPE' => 'REST',
							'REST_APP_ID' => $restAppId,
							'CRM_AUTO_CREATE' => $externalNumber['CRM_AUTO_CREATE'],
						) ;
					}
				}
			}
		}

		$cache[$cacheKey] = $result;

		return $cache[$cacheKey];
	}

	public static function GetLine($lineId)
	{
		$lines = self::GetLines(true, true);
		return (isset($lines[$lineId]) ? $lines[$lineId] : false);
	}

	public static function GetConfigurations()
	{
		$result = [];
		$res = VI\ConfigTable::getList([
			'select' => [
				'ID',
				'PORTAL_MODE',
				'SEARCH_ID',
				'PHONE_NAME',
				'PHONE_NUMBER' => 'NUMBER.NUMBER',
				'CALLER_ID_NUMBER' => 'CALLER_ID.NUMBER'
			],
		]);
		while ($row = $res->fetch())
		{
			if ($row['SEARCH_ID'] == 'test')
				continue;

			if ($row['PORTAL_MODE'] == static::MODE_SIP)
			{
				$name = $row['PHONE_NAME'] ?: static::GetDefaultPhoneName($row);
			}
			else if ($row['PORTAL_MODE'] == static::MODE_RENT)
			{
				$name = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($row['PHONE_NUMBER'])->format(PhoneNumber\Format::INTERNATIONAL);
			}
			else if ($row['PORTAL_MODE'] == static::MODE_LINK)
			{
				$name = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($row['CALLER_ID_NUMBER'])->format(PhoneNumber\Format::INTERNATIONAL);
			}
			else
			{
				$name = $row['PHONE_NAME'];
			}

			$result[] = array(
				'ID' => $row['ID'],
				'NAME' =>  $name,
				'TYPE' => $row['PORTAL_MODE']
			);
		}

		return $result;
	}

	public static function GetCallbackNumbers()
	{
		$result = static::GetPortalNumbers(true, false);
		$restApps = VI\Rest\Helper::getExternalCallbackHandlers();
		$externalNumbers = array();
		$externalNumbersCursor = VI\Model\ExternalLineTable::getList();
		foreach ($externalNumbersCursor->getIterator() as $row)
		{
			$externalNumbers[$row['REST_APP_ID']][] = $row;
		}
		foreach ($restApps as $restAppId => $restAppName)
		{
			$prefixedRestAppId = CVoxImplantConfig::MODE_REST_APP . ':' . $restAppId;
			$result[$prefixedRestAppId] = GetMessage("VI_CONFIG_REST_APP").": ".$restAppName;
			if($externalNumbers[$restAppId])
			{
				foreach ($externalNumbers[$restAppId] as $externalNumber)
				{
					$result[$externalNumber['NUMBER']] =  GetMessage("VI_CONFIG_REST_APP").": ".$restAppName. ": " . ($externalNumber['NAME'] ?  $externalNumber['NUMBER'] . " - " . $externalNumber['NAME'] : $externalNumber['NUMBER']);
				}
			}
		}
		return $result;
	}

	public static function GetDefaultPhoneName($config)
	{
		$result = '';
		if($config['PORTAL_MODE'] === self::MODE_SIP)
		{
			// VI_CONFIG_SIP_OFFICE_DEF and VI_CONFIG_SIP_CLOUD_DEF have wrong values here, it's ok
			$result = mb_substr($config['SEARCH_ID'], 0, 3) == 'reg'? GetMessage('VI_CONFIG_SIP_OFFICE_DEF'): GetMessage('VI_CONFIG_SIP_CLOUD_DEF');
			$result = str_replace('#ID#', $config['ID'], $result);
		}
		else if($config['PORTAL_MODE'] === self::MODE_RENT)
		{
			$row = VI\Model\NumberTable::getRow([
				'select' => ['NUMBER'],
				'filter' => [
					'=CONFIG_ID' => $config['ID']
				]
			]);
			$result = CVoxImplantPhone::formatInternational($row['NUMBER']);
		}
		else if($config['PORTAL_MODE'] === self::MODE_LINK)
		{
			$row = VI\Model\CallerIdTable::getRow([
				'select' => ['NUMBER'],
				'filter' => [
					'=CONFIG_ID' => $config['ID']
				]
			]);
			$result = CVoxImplantPhone::formatInternational($row['NUMBER']);
		}

		return $result;
	}

	public static function GetPhoneName($config)
	{
		$result = '';
		if($config['PHONE_NAME'] != '')
		{
			$result = $config['PHONE_NAME'];
		}
		else if($config['PORTAL_MODE'] == self::MODE_SIP)
		{
			$result = mb_substr($config['SEARCH_ID'], 0, 3) == 'reg'? GetMessage('VI_CONFIG_SIP_CLOUD_DEF'): GetMessage('VI_CONFIG_SIP_OFFICE_DEF');
			$result = str_replace('#ID#', $config['ID'], $result);
		}
		else if($config['PORTAL_MODE'] === self::MODE_LINK)
		{
			$linkNumber = CVoxImplantPhone::GetLinkNumber();
			$result = ($linkNumber == ''? GetMessage('VI_CONFIG_LINK_DEF'): '+'.$linkNumber);
		}

		return $result;
	}

	public static function GetModeStatus($mode)
	{
		if (!in_array($mode, Array(self::MODE_LINK, self::MODE_RENT, self::MODE_SIP)))
			return false;

		if ($mode == self::MODE_SIP)
		{
			return COption::GetOptionString("main", "~PARAM_PHONE_SIP", 'N') == 'Y';
		}

		return COption::GetOptionString("voximplant", "mode_".mb_strtolower($mode));
	}

	public static function SetModeStatus($mode, $enable)
	{
		if (!in_array($mode, Array(self::MODE_LINK, self::MODE_RENT, self::MODE_SIP)))
			return false;

		if ($mode == self::MODE_SIP)
		{
			COption::SetOptionString("main", "~PARAM_PHONE_SIP", $enable? 'Y': 'N');
		}
		else
		{
			COption::SetOptionString("voximplant", "mode_".mb_strtolower($mode), $enable? true: false);
		}

		return true;
	}

	public static function GetChatAction()
	{
		return COption::GetOptionString("voximplant", "interface_chat_action");
	}

	public static function SetChatAction($action)
	{
		if (!in_array($action, Array(self::INTERFACE_CHAT_ADD, self::INTERFACE_CHAT_APPEND, self::INTERFACE_CHAT_NONE)))
			return false;

		COption::SetOptionString("voximplant", "interface_chat_action", $action);

		return true;
	}

	public static function GetLeadWorkflowExecution()
	{
		if (!CVoxImplantCrmHelper::isLeadEnabled())
		{
			return self::WORKFLOW_START_DEFERRED;
		}

		return COption::GetOptionString("voximplant", "lead_workflow_execution", self::WORKFLOW_START_DEFERRED);
	}

	public static function SetLeadWorkflowExecution($executionParameter)
	{
		if (!in_array($executionParameter, Array(self::WORKFLOW_START_IMMEDIATE, self::WORKFLOW_START_DEFERRED)))
			return false;

		COption::SetOptionString("voximplant", "lead_workflow_execution", $executionParameter);
		return true;
	}

	public static function GetCombinationInterceptGroup()
	{
		return COption::GetOptionString("voximplant", "combination_intercept_group");
	}

	public static function SetCombinationInterceptGroup($combinationInterceptGroup)
	{
		if(preg_match('/[^\d*#]/', $combinationInterceptGroup))
			return false;

		COption::SetOptionString("voximplant", "combination_intercept_group", $combinationInterceptGroup);
		return true;
	}

	public static function GetLinkCallRecord()
	{
		return COption::GetOptionInt("voximplant", "link_call_record");
	}

	public static function SetLinkCallRecord($active)
	{
		$active = $active? true: false;

		return COption::SetOptionInt("voximplant", "link_call_record", $active);
	}

	public static function GetLinkCheckCrm()
	{
		return COption::GetOptionInt("voximplant", "link_check_crm");
	}

	public static function SetLinkCheckCrm($active)
	{
		$active = $active? true: false;

		return COption::SetOptionInt("voximplant", "link_check_crm", $active);
	}

	public static function GetMelodyLanguages()
	{
		return array('EN', 'DE', 'RU', 'UA', 'ES', 'BR');
	}

	public static function GetDefaultMelodies($lang = 'EN')
	{
		if ($lang !== false)
		{
			$lang = mb_strtoupper($lang);
			if ($lang == 'KZ')
			{
				$lang = 'RU';
			}
			else if (!in_array($lang, static::GetMelodyLanguages()))
			{
				$lang = 'EN';
			}
		}
		else
		{
			$lang = '#LANG_ID#';
		}

		return array(
			"MELODY_WELCOME" => "https://dl.bitrix24.com/telephony/".$lang."01.mp3",
			"MELODY_WAIT" => "https://dl.bitrix24.com/telephony/MELODY.mp3",
			"MELODY_ENQUEUE" => "https://dl.bitrix24.com/telephony/".$lang."07.mp3",
			"MELODY_HOLD" => "https://dl.bitrix24.com/telephony/MELODY.mp3",
			"MELODY_VOICEMAIL" => "https://dl.bitrix24.com/telephony/".$lang."03.mp3",
			"MELODY_VOTE" => "https://dl.bitrix24.com/telephony/".$lang."04.mp3",
			"MELODY_VOTE_END" => "https://dl.bitrix24.com/telephony/".$lang."05.mp3",
			"MELODY_RECORDING" => "https://dl.bitrix24.com/telephony/".$lang."06.mp3",
			"WORKTIME_DAYOFF_MELODY" => "https://dl.bitrix24.com/telephony/".$lang."03.mp3",
		);
	}

	public static function GetMelody($name, $lang = 'EN', $fileId = 0)
	{
		$fileId = intval($fileId);

		$result = '';
		if ($fileId > 0)
		{
			$res = CFile::GetFileArray($fileId);
			if ($res && $res['MODULE_ID'] == 'voximplant')
			{
				if (mb_substr($res['SRC'], 0, 4) == 'http' || mb_substr($res['SRC'], 0, 2) == '//')
				{
					$result = $res['SRC'];
				}
				else
				{
					$result = CVoxImplantHttp::GetServerAddress().$res['SRC'];
				}
			}
		}

		if ($result == '')
		{
			$default = CVoxImplantConfig::GetDefaultMelodies($lang);
			$result = isset($default[$name])? $default[$name]: '';
		}

		return $result;
	}

	public static function GetConfigBySearchId($searchId)
	{
		return self::GetConfig($searchId, self::GET_BY_SEARCH_ID);
	}

	public static function GetConfig($id, $type = self::GET_BY_ID)
	{
		if ($id == '')
		{
			return Array('ERROR' => 'Config is`t found for undefined id/number');
		}

		$filter = array();
		if($type === self::GET_BY_SEARCH_ID)
		{
			$filter = [
				'LOGIC' => 'OR',
				'=SEARCH_ID' => (string)$id,
				'=NUMBER.NUMBER' => (string)$id,
				'=GROUP_NUMBER.NUMBER' => (string)$id,
				'=CALLER_ID.NUMBER' => (string)$id,
			];

		}
		else
		{
			$filter['=ID'] = (int)$id;
		}

		$orm = VI\ConfigTable::getList(array(
			'select' => array(
				'*',
				'NO_ANSWER_RULE' => 'QUEUE.NO_ANSWER_RULE',
				'QUEUE_TYPE' => 'QUEUE.TYPE',
				'QUEUE_TIME' => 'QUEUE.WAIT_TIME', // compatibility fix
				'FORWARD_NUMBER' => 'QUEUE.FORWARD_NUMBER',
				'NUMBER_COUNTRY_CODE' => 'NUMBER.COUNTRY_CODE'
			),
			'filter' => $filter
		));

		$config = $orm->fetch();
		if (!$config)
		{
			return array(
				'ERROR' => $type == self::GET_BY_SEARCH_ID? 'Config is`t found for number: '.$id: 'Config is`t found for id: '.$id
			);
		}

		$result = $config;

		$result['PHONE_TITLE'] = $result['PHONE_NAME'];
		if ($result['PORTAL_MODE'] == self::MODE_LINK)
		{
			$row = VI\Model\CallerIdTable::getRow([
				'filter' => [
					'=CONFIG_ID' => $config['ID']
				]
			]);
			$result['SEARCH_ID'] = $result['PHONE_TITLE'] = $result['PHONE_NAME'] = $row['NUMBER'];
		}
		else if($result['PORTAL_MODE'] == self::MODE_RENT)
		{
			$row = VI\Model\NumberTable::getRow([
				'filter' => [
					'=CONFIG_ID' => $config['ID']
				]
			]);
			$result['SEARCH_ID'] = $result['PHONE_TITLE'] = $result['PHONE_NAME'] = $row['NUMBER'];
		}
		else if($result['PORTAL_MODE'] == self::MODE_GROUP)
		{
			if($type == self::GET_BY_SEARCH_ID)
			{
				$result['SEARCH_ID'] = $result['PHONE_TITLE'] = $result['PHONE_NAME'] = $id;
			}
		}
		else if ($result['PORTAL_MODE'] == self::MODE_SIP)
		{
			$viSip = new CVoxImplantSip();
			$sipResult = $viSip->Get($config["ID"]);

			$result['PHONE_NAME'] = preg_replace("/[^0-9\#\*]/i", "", $result['PHONE_NAME']);
			$result['PHONE_NAME'] = mb_strlen($result['PHONE_NAME']) >= 4? $result['PHONE_NAME']: '';

			if($sipResult)
			{
				$result['SIP_ID'] = $sipResult['ID'];
				$result['SIP_SERVER'] = $sipResult['SERVER'];
				$result['SIP_LOGIN'] = $sipResult['LOGIN'];
				$result['SIP_PASSWORD'] = $sipResult['PASSWORD'];
				$result['SIP_TYPE'] = $sipResult['TYPE'];
				$result['SIP_REG_ID'] = $sipResult['REG_ID'];
				$result['SIP_DETECT_LINE_NUMBER'] = $sipResult['DETECT_LINE_NUMBER'];
				$result['SIP_LINE_DETECT_HEADER_ORDER'] = $sipResult['LINE_DETECT_HEADER_ORDER'];
			}
			else
			{
				$result['SIP_SERVER'] = '';
				$result['SIP_LOGIN'] = '';
				$result['SIP_PASSWORD'] = '';
				$result['SIP_TYPE'] = '';
				$result['SIP_REG_ID'] = '';
				$result['SIP_DETECT_LINE_NUMBER'] = '';
				$result['SIP_LINE_DETECT_HEADER_ORDER'] = '';
			}
		}

		if ($result['FORWARD_LINE'] != '' && $result['FORWARD_LINE'] != self::FORWARD_LINE_DEFAULT)
		{
			$result['FORWARD_LINE'] = self::GetBriefConfig(array(
				'SEARCH_ID' => $result['FORWARD_LINE']
			));
		}

		if ($result['BACKUP_NUMBER'] == '')
		{
			$result['BACKUP_NUMBER'] = static::getCommonBackupNumber();
			$result['BACKUP_LINE'] = static::getCommonBackupLine();
		}

		if ($result['BACKUP_NUMBER'] != '' && $result['BACKUP_LINE'] != '')
		{
			$result['BACKUP_LINE'] = self::GetBriefConfig(['SEARCH_ID' => $result['BACKUP_LINE']]);
		}

		if ($result['FORWARD_NUMBER'] <> '')
		{
			$result["FORWARD_NUMBER"] = NormalizePhone($result['FORWARD_NUMBER'], 1);
		}

		if ($result['WORKTIME_DAYOFF_NUMBER'] <> '')
		{
			$result["WORKTIME_DAYOFF_NUMBER"] = NormalizePhone($result['WORKTIME_DAYOFF_NUMBER'], 1);
		}
		// check work time
		$result['WORKTIME_SKIP_CALL'] = 'N';
		if ($config['WORKTIME_ENABLE'] == 'Y')
		{
			$timezone = (!empty($config["WORKTIME_TIMEZONE"])) ? new DateTimeZone($config["WORKTIME_TIMEZONE"]) : null;
			$numberDate = new Bitrix\Main\Type\DateTime(null, null, $timezone);

			if (!empty($config['WORKTIME_DAYOFF']))
			{
				$daysOff = explode(",", $config['WORKTIME_DAYOFF']);

				$allWeekDays = array('MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6, 'SU' => 7);
				$currentWeekDay = $numberDate->format('N');
				foreach($daysOff as $day)
				{
					if ($currentWeekDay == $allWeekDays[$day])
					{
						$result['WORKTIME_SKIP_CALL'] = "Y";
					}
				}
			}
			if ($result['WORKTIME_SKIP_CALL'] !== "Y" && !empty($config['WORKTIME_HOLIDAYS']))
			{
				$holidays = explode(",", $config['WORKTIME_HOLIDAYS']);
				$currentDay = $numberDate->format('d.m');

				foreach($holidays as $holiday)
				{
					if ($currentDay == $holiday)
					{
						$result['WORKTIME_SKIP_CALL'] = "Y";
					}
				}
			}
			if ($result['WORKTIME_SKIP_CALL'] !== "Y" && isset($config['WORKTIME_FROM']) && isset($config['WORKTIME_TO']))
			{
				$currentTime = $numberDate->format('G.i');

				if (!($currentTime >= $config['WORKTIME_FROM'] && $currentTime <= $config['WORKTIME_TO']))
				{
					$result['WORKTIME_SKIP_CALL'] = "Y";
				}
			}

			if ($result['WORKTIME_SKIP_CALL'] === "Y")
			{
				$result['WORKTIME_DAYOFF_MELODY'] =  CVoxImplantConfig::GetMelody('WORKTIME_DAYOFF_MELODY', $config['MELODY_LANG'], $config['WORKTIME_DAYOFF_MELODY']);
			}
		}

		if ($result['IVR'] == 'Y' && !VI\Ivr\Ivr::isEnabled())
			$result['IVR'] = 'N';

		if($result['RECORDING'] == 'Y')
		{
			$recordLimit = VI\Limits::getRecordLimit($result['PORTAL_MODE']);
			$recordRemain = VI\Limits::getRemainingRecordsCount();

			$result['RECORDING_ALLOWED'] = ($recordLimit == 0 || $recordRemain > 0) ? 'Y' : 'N';
		}

		if($result['TRANSCRIBE'] == 'Y' && !VI\Transcript::isEnabled())
			$result['TRANSCRIBE'] = 'N';

		$result['PORTAL_URL'] = CVoxImplantHttp::GetPortalUrl();
		$result['PORTAL_SIGN'] = CVoxImplantHttp::GetPortalSign();
		$result['MELODY_WELCOME'] = CVoxImplantConfig::GetMelody('MELODY_WELCOME', $config['MELODY_LANG'], $config['MELODY_WELCOME']);
		$result['MELODY_VOICEMAIL'] =  CVoxImplantConfig::GetMelody('MELODY_VOICEMAIL', $config['MELODY_LANG'], $config['MELODY_VOICEMAIL']);
		$result['MELODY_HOLD'] =  CVoxImplantConfig::GetMelody('MELODY_HOLD', $config['MELODY_LANG'], $config['MELODY_HOLD']);
		$result['MELODY_WAIT'] =  CVoxImplantConfig::GetMelody('MELODY_WAIT', $config['MELODY_LANG'], $config['MELODY_WAIT']);
		$result['MELODY_ENQUEUE'] =  CVoxImplantConfig::GetMelody('MELODY_ENQUEUE', $config['MELODY_LANG'], $config['MELODY_ENQUEUE']);
		$result['MELODY_RECORDING'] =  CVoxImplantConfig::GetMelody('MELODY_RECORDING', $config['MELODY_LANG'], $config['MELODY_RECORDING']);
		$result['MELODY_VOTE'] =  CVoxImplantConfig::GetMelody('MELODY_VOTE', $config['MELODY_LANG'], $config['MELODY_VOTE']);
		$result['MELODY_VOTE_END'] =  CVoxImplantConfig::GetMelody('MELODY_VOTE_END', $config['MELODY_LANG'], $config['MELODY_VOTE_END']);
		$result['MODULE_VERSION'] = CVoxImplantHttp::VERSION;

		return $result;
	}

	/**
	 * Returns brief line config, containing only parameters, required for making an outgoing call.
	 * @param array $params Search parameters
	 * <li>ID int Search config by id;
	 * <li>SEARCH_ID string Search config by search_id.
	 * @return array|false Returns array with parameters if config is found or false otherwise.
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function GetBriefConfig($params)
	{
		$filter = array();

		if(isset($params['ID']))
		{
			$filter['=ID'] = $params['ID'];
		}
		else if (isset($params['SEARCH_ID']))
		{
			$searchId = $params['SEARCH_ID'];

			$filter = [
				'LOGIC' => 'OR',
				'=SEARCH_ID' => (string)$searchId,
				'=NUMBER.NUMBER' => (string)$searchId,
				'=GROUP_NUMBER.NUMBER' => (string)$searchId,
				'=CALLER_ID.NUMBER' => (string)$searchId,
			];
		}
		else
		{
			throw new \Bitrix\Main\ArgumentException('Params should contain either ID or SEARCH_ID', 'params');
		}

		$result = VI\ConfigTable::getList(array(
			'select' => array(
				'ID' => 'ID',
				'PHONE_NAME' => 'PHONE_NAME',
				'SEARCH_ID' => 'SEARCH_ID',
				'LINE_TYPE' => 'PORTAL_MODE',
				'LINE_NUMBER' => 'SEARCH_ID',
				'SIP_TYPE' => 'SIP_CONFIG.TYPE',
				'SIP_REG_ID' => 'SIP_CONFIG.REG_ID',
				'SIP_SERVER' => 'SIP_CONFIG.SERVER',
				'SIP_LOGIN' => 'SIP_CONFIG.LOGIN',
				'SIP_PASSWORD' => 'SIP_CONFIG.PASSWORD'
			),
			'filter' => $filter,
		))->fetch();

		if(!$result)
			return false;

		if ($result['LINE_TYPE'] == self::MODE_LINK)
		{
			$row = VI\Model\CallerIdTable::getRow([
				'filter' => [
					'=CONFIG_ID' => $result['ID']
				]
			]);
			$result['SEARCH_ID'] = $result['LINE_NUMBER'] = $result['PHONE_NAME'] = $row['NUMBER'];
		}
		else if($result['LINE_TYPE'] == self::MODE_RENT)
		{
			$row = VI\Model\NumberTable::getRow([
				'filter' => [
					'=CONFIG_ID' => $result['ID']
				]
			]);
			$result['SEARCH_ID'] = $result['LINE_NUMBER'] = $result['PHONE_NAME'] = $row['NUMBER'];
		}

		if ($result['LINE_TYPE'] === self::MODE_SIP && $result['SIP_TYPE'] === CVoxImplantSip::TYPE_CLOUD)
		{
			// password is not required in this case, because call is performed by REG_ID
			$result['SIP_PASSWORD'] = '';
		}

		return $result;
	}

	public static function GetNoticeOldConfigOfficePbx()
	{
		$result = false;
		$permission = VI\Security\Permissions::createWithCurrentUser();
		if (COption::GetOptionString("voximplant", "notice_old_config_office_pbx") == 'Y' && $permission->canPerform(VI\Security\Permissions::ENTITY_LINE, VI\Security\Permissions::ACTION_MODIFY))
		{
			$result = true;
		}

		return $result;
	}

	public static function HideNoticeOldConfigOfficePbx()
	{
		$result = false;

		COption::SetOptionString("voximplant", "notice_old_config_office_pbx", 'N');

		return $result;
	}

	/**
	 * Returns true if line with provided search id is served via rest application
	 * @param string $searchId
	 * @return boolean
	 */
	public static function isRestApp($searchId)
	{
		$numberParameters = explode(':', $searchId);
		return ($numberParameters[0] === self::MODE_REST_APP);
	}

	public static function getConfigForPopup($callId)
	{
		$call = VI\Model\CallTable::getByCallId($callId);
		if(!$call || !isset($call['CONFIG_ID']))
			return false;

		$config = VI\ConfigTable::getRowById($call['CONFIG_ID']);
		if(!$config)
			return false;

		$result = array(
			'RECORDING' => $config['RECORDING']
		);

		if(
			   $config['CRM_CREATE_CALL_TYPE'] == CVoxImplantConfig::CRM_CREATE_CALL_TYPE_ALL
			|| $config['CRM_CREATE_CALL_TYPE'] == CVoxImplantConfig::CRM_CREATE_CALL_TYPE_INCOMING && $call['INCOMING'] == CVoxImplantMain::CALL_INCOMING
			|| $config['CRM_CREATE_CALL_TYPE'] == CVoxImplantConfig::CRM_CREATE_CALL_TYPE_OUTGOING && $call['INCOMING'] == CVoxImplantMain::CALL_OUTGOING
		)
		{
			$result['CRM_CREATE'] = $config['CRM_CREATE'];
		}
		else
		{
			$result['CRM_CREATE'] = CVoxImplantConfig::CRM_CREATE_NONE;
		}
		return $result;
	}

	public static function setCommonBackupNumber($backupNumber, $backupLine)
	{
		$backupNumber = CVoxImplantPhone::Normalize($backupNumber);
		COption::SetOptionString("voximplant", "common_backup_number", $backupNumber);
		COption::SetOptionString("voximplant", "common_backup_line", $backupLine);

		$controllerApi = new CVoxImplantHttp();
		if($backupNumber && $backupLine)
		{
			$controllerApi->setCommonBackupNumber(
				$backupNumber,
				static::GetBriefConfig(['SEARCH_ID'=> $backupLine])
			);
		}
		else
		{
			$controllerApi->deleteBackupNumber(static::BACKUP_NUMBER_COMMON);
		}
	}

	public static function getCommonBackupNumber()
	{
		return COption::GetOptionString("voximplant", "common_backup_number");
	}

	public static function getCommonBackupLine()
	{
		return COption::GetOptionString("voximplant", "common_backup_line");
	}

	public static function saveBackupNumber($lineId, $backupNumber, $backupLine)
	{
		$controllerApi = new CVoxImplantHttp();
		if($backupNumber)
		{
			$controllerApi->setLineBackupNumber(
				$lineId,
				$backupNumber,
				static::GetBriefConfig(['SEARCH_ID'=> $backupLine])
			);
		}
		else
		{
			$controllerApi->deleteBackupNumber(static::BACKUP_NUMBER_SPECIFIC);
		}
	}

	public static function deleteOrphanConfigurations()
	{
		$cursor = \Bitrix\Voximplant\ConfigTable::getList([
			'select' => ['ID'],
			'filter' => [
				'LOGIC' => 'OR',
				[
					'=PORTAL_MODE' => CVoxImplantConfig::MODE_LINK,
					'HAS_CALLER_ID' => 'N'
				],
				[
					'=PORTAL_MODE' => CVoxImplantConfig::MODE_SIP,
					'HAS_SIP_CONNECTION' => 'N'
				],
				[
					'=PORTAL_MODE' => CVoxImplantConfig::MODE_RENT,
					'HAS_NUMBER' => 'N'
				],
				[
					'=PORTAL_MODE' => CVoxImplantConfig::MODE_GROUP,
					'HAS_NUMBER' => 'N'
				]
			]
		]);

		while ($row = $cursor->fetch())
		{
			VI\ConfigTable::delete($row['ID']);
		}
	}

	public static function clearUserCache($number = '', $sendPull = true): void
	{
		CVoxImplantUser::clearCache();

		$users = CVoxImplantUser::getOnlineUsersWithNotDefaultNumber();
		if(!empty($users) && $sendPull)
		{
			VI\Integration\Pull::sendDefaultLineId($users, $number);
		}
	}
}
