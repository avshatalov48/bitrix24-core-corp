<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type as FieldType;
use Bitrix\Voximplant as VI;
use Bitrix\Voximplant\Tts;
use Bitrix\Voximplant\Model\CallTable;

class CVoxImplantOutgoing
{
	const INFOCALL_MODE_TEXT = 'text';
	const INFOCALL_MODE_URL = 'url';

	public static function GetConfig($userId, $lineId = '')
	{
		$userId = intval($userId);

		if($userId === 0)
			return array('error' => array('code' => 'EMPTY_USER_ID', 'msg' => 'userId should be positive'));

		$viUser = new CVoxImplantUser();
		$userInfo = $viUser->GetUserInfo($userId);

		if(!$userInfo)
			return array('error' => array('code' => $viUser->GetError()->code, 'msg' => $viUser->GetError()->msg));

		if($userInfo['user_extranet'])
			return array('error' => array('code' => 'EXTRANAET', 'msg' => 'Extranet user (or user hasnt department) can not use telephony'));

		$isPaid = CVoxImplantAccount::GetPayedFlag() === "Y";
		$portalLines = CVoxImplantConfig::GetLines(true, false);

		$userDefaultLine = $userInfo['user_backphone'];
		if (!isset($portalLines[$userDefaultLine]))
		{
			$defaultPortalLine = COption::GetOptionString("voximplant", "portal_number");
			if(isset($portalLines[$defaultPortalLine]))
			{
				$userDefaultLine = $defaultPortalLine;
			}
			else if(count($portalLines) > 0)
			{
				reset($portalLines);
				$userDefaultLine = key($portalLines);
			}
			else if(!$isPaid)
			{
				return array(
					'PORTAL_MODE' => CVoxImplantConfig::MODE_FAKE,
					'PORTAL_URL' => CVoxImplantHttp::GetPortalUrl(),
					'PORTAL_SIGN' => CVoxImplantHttp::GetPortalSign(),
					'USER_ID' => $userId,
					'USER_DIRECT_CODE' => $userInfo['user_innerphone'],
				);
			}
			else
			{
				return array('error' => array('code' => 'NEED_RENT_ERROR', 'msg' => 'No available lines found'));
			}
		}

		if($lineId != '')
		{
			if(isset($portalLines[$lineId]))
			{
				if(!CVoxImplantUser::canUseLine($userId, $lineId))
				{
					$lineId = '';
				}
			}
			else
			{
				$lineId = '';
			}
		}

		$result = CVoxImplantConfig::GetConfigBySearchId($lineId ?: $userDefaultLine);
		$result['USER_ID'] = $userId;
		$result['USER_DIRECT_CODE'] = $userInfo['user_innerphone'];

		return $result;
	}

	/**
	 * Finds output line for the dialed number by the prefix.
	 * @param string $phoneNumber Dialed number.
	 * @return string Returns search_id of the line if found or false otherwise.
	 */
	public static function findLineId($phoneNumber)
	{
		$phoneNumber = CVoxImplantPhone::stripLetters($phoneNumber);

		$checkExtensionCursor = \Bitrix\Main\UserTable::getList(Array(
			'select' => Array('ID', 'IS_ONLINE', 'UF_VI_PHONE', 'ACTIVE'),
			'filter' => Array('=UF_PHONE_INNER' => intval($phoneNumber), '=ACTIVE' => 'Y'),
		));
		if($checkExtensionCursor->fetch())
		{
			return false;
		}

		$cursor = VI\ConfigTable::getList(array(
			'select' => array('SEARCH_ID', 'LINE_PREFIX'),
			'filter' => array(
				'=CAN_BE_SELECTED' => 'Y',
			)
		));
		while ($row = $cursor->fetch())
		{
			$currentPrefix = (string)$row['LINE_PREFIX'];
			if($currentPrefix == '')
				continue;

			if($currentPrefix == substr($phoneNumber, 0, strlen($currentPrefix)))
			{
				return $row['SEARCH_ID'];
			}
		}
		return false;
	}

	/**
	 * @param $params
	 * @return VI\Routing\Action|false
	 */
	public static function Init($params)
	{
		$config = CVoxImplantConfig::GetConfig($params['CONFIG_ID']);
		if ($params['CALL_ID'])
		{
			$call = VI\Call::load($params['CALL_ID']);
			if($call)
			{
				$callFields = [
					'CONFIG_ID' => $params['CONFIG_ID'],
					'CRM' => $params['CRM'],
					'USER_ID' => $params['USER_ID'],
					'CALLER_ID' => $params['PHONE_NUMBER'],
					'STATUS' => VI\Model\CallTable::STATUS_WAITING,
					'ACCESS_URL' => $params['ACCESS_URL'],
					'PORTAL_NUMBER' => $config['SEARCH_ID'],
				];

				if($params['CRM_ENTITY_TYPE'] && $params['CRM_ENTITY_ID'])
				{
					$callFields['CRM_ENTITY_TYPE'] = $params['CRM_ENTITY_TYPE'];
					$callFields['CRM_ENTITY_ID'] = $params['CRM_ENTITY_ID'];
				}
				if($params['CRM_ACTIVITY_ID'])
				{
					$callFields['CRM_ACTIVITY_ID'] = $params['CRM_ACTIVITY_ID'];
				}
				if($params['CRM_CALL_LIST'])
				{
					$callFields['CRM_CALL_LIST'] = $params['CRM_CALL_LIST'];
				}
				if(is_array($params['CRM_BINDINGS']))
				{
					$callFields['CRM_BINDINGS'] = $params['CRM_BINDINGS'];
				}
				if($params['SESSION_ID'])
				{
					$callFields['SESSION_ID'] = (int)$params['SESSION_ID'];
				}

				$call->update($callFields);

			}
		}
		if (!$call)
		{
			$call = VI\Call::create([
				'INCOMING' => CVoxImplantMain::CALL_OUTGOING,
				'CONFIG_ID' => $params['CONFIG_ID'],
				'CALL_ID' => $params['CALL_ID'],
				'SESSION_ID' => (int)$params['SESSION_ID'],
				'CRM' => $params['CRM'],
				'CRM_ACTIVITY_ID' => $params['CRM_ACTIVITY_ID'] ?? null,
				'CRM_CALL_LIST' => $params['CRM_CALL_LIST'] ?? null,
				'CRM_BINDINGS' => $params['CRM_BINDINGS'] ?? [],
				'USER_ID' => $params['USER_ID'],
				'CALLER_ID' => $params['PHONE_NUMBER'],
				'STATUS' => VI\Model\CallTable::STATUS_WAITING,
				'ACCESS_URL' => $params['ACCESS_URL'],
				'DATE_CREATE' => new FieldType\DateTime(),
				'PORTAL_NUMBER' => $config['SEARCH_ID'],
			]);
		}
		$call->addUsers([$params['USER_ID']], VI\Model\CallUserTable::ROLE_CALLER, VI\Model\CallUserTable::STATUS_CONNECTED);

		if($params['CRM_ENTITY_TYPE'] != '' && $params['CRM_ENTITY_ID'] > 0)
		{
			$entity = [
				'ENTITY_TYPE' => $params['CRM_ENTITY_TYPE'],
				'ENTITY_ID' => $params['CRM_ENTITY_ID'],
				'IS_PRIMARY' => 'Y',
				'IS_CREATED' => 'N'
			];
			$call->updateCrmEntities([$entity]);
		}

		$router = new VI\Routing\Router($call);
		$firstAction = $router->getNextAction();

		if($firstAction->getCommand() === VI\Routing\Command::INVITE || VI\Routing\Command::BUSY)
		{
			// looks like internal call
			$firstUser = $firstAction->getParameter('USERS')[0];
			$call->updatePortalUserId($firstUser['USER_ID']);
		}

		if (!$call->isInternalCall() && $call->isCrmEnabled())
		{
			if($call->getPrimaryEntityType() && $call->getPrimaryEntityId())
			{
				//nop
			}
			else
			{
				$crmEntities = CVoxImplantCrmHelper::getCrmEntities($call);
				$call->updateCrmEntities($crmEntities);
			}

			$activityBindings = CVoxImplantCrmHelper::getActivityBindings($call);
			if(is_array($activityBindings))
			{
				$call->updateCrmBindings($activityBindings);
			}

			CVoxImplantCrmHelper::registerCallInCrm($call);
			$crmData = CVoxImplantCrmHelper::GetDataForPopup($params['CALL_ID'], $params['PHONE_NUMBER'], $params['USER_ID']);
		}
		else
		{
			$crmData = Array();
		}

		CVoxImplantHistory::WriteToLog(Array(
			'COMMAND' => 'outgoing',
			'USER_ID' => $params['USER_ID'],
			'CALL_ID' => $params['CALL_ID'],
			'CALL_DEVICE' => $params['CALL_DEVICE'],
			'PHONE_NUMBER' => $params['PHONE_NUMBER'],
			'PORTAL_CALL_USER_ID' => $params['USER_ID'],
			'CRM' => $crmData,
			'CRM_ENTITY_TYPE' => $params['CRM_ENTITY_TYPE'],
			'CRM_ENTITY_ID' => $params['CRM_ENTITY_ID'],
			'CRM_ACTIVITY_ID' => $params['CRM_ACTIVITY_ID'],
		));

		if ($call->isInternalCall() && CModule::IncludeModule('im'))
		{
			$portalUser = CIMContactList::GetUserData(['ID' => $call->getUserIds(), 'DEPARTMENT' => 'N', 'HR_PHOTO' => 'Y']);
		}
		else
		{
			$portalUser = Array();
		}

		self::SendPullEvent(Array(
			'COMMAND' => 'outgoing',
			'USER_ID' => $call->getUserId(),
			'CALL_ID' => $call->getCallId(),
			'CALL_DEVICE' => $params['CALL_DEVICE'],
			'PHONE_NUMBER' => $call->getCallerId(),
			'PORTAL_CALL' => $call->isInternalCall() ? 'Y' : 'N',
			'PORTAL_CALL_USER_ID' => $call->getPortalUserId(),
			'PORTAL_CALL_DATA' => $portalUser,
			'CONFIG' => CVoxImplantConfig::getConfigForPopup($call->getCallId()),
			'PORTAL_NUMBER' => $config['SEARCH_ID'],
			'PORTAL_NUMBER_NAME' => $config['PORTAL_MODE'] == CVoxImplantConfig::MODE_SIP ? $config['PHONE_TITLE'] : $config['PHONE_NAME'],
			'CRM' => $crmData,
		));

		return $firstAction;
	}

	public static function GetConfigByUserId($userId)
	{
		$userId = (int)$userId;
		if ($userId > 0)
		{
			$viUser = new CVoxImplantUser();
			$userInfo = $viUser->GetUserInfo($userId);
			if ($userInfo['user_backphone'] == '')
			{
				$userInfo['user_backphone'] = CVoxImplantConfig::LINK_BASE_NUMBER;
			}
		}
		else
		{
			$userInfo = Array();
			$userInfo['user_backphone'] = CVoxImplantConfig::GetPortalNumber();
			$userInfo['user_extranet'] = false;
			$userInfo['user_innerphone'] = CVoxImplantConfig::GetPortalNumber();
		}

		if ($userInfo['user_extranet'])
		{
			$result = Array('error' => Array('code' => 'EXTRANAET', 'msg' => 'Extranet user (or user hasnt department) cannot use telephony'));
		}
		else
		{
			$result = CVoxImplantConfig::GetConfigBySearchId($userInfo['user_backphone']);
		}

		$result['USER_ID'] = $userId;
		$result['USER_DIRECT_CODE'] = $userInfo['user_innerphone'];

		return $result;
	}

	public static function SendPullEvent($params)
	{
		// TODO check params

		if (!CModule::IncludeModule('pull') || !CPullOptions::GetQueueServerStatus() || $params['USER_ID'] <= 0)
			return false;

		$config = Array();
		$push = Array();
		if ($params['COMMAND'] == 'outgoing')
		{
			$call = VI\Call::load($params['CALL_ID']);

			$config = Array(
				"callId" => $call->getCallId(),
				"callDevice" => $params['CALL_DEVICE'] == 'PHONE'? 'PHONE': 'WEBRTC',
				"phoneNumber" => $params['PHONE_NUMBER'],
				"portalCall" => $params['PORTAL_CALL'] == 'Y'? true: false,
				"portalCallUserId" => $params['PORTAL_CALL'] == 'Y'? $params['PORTAL_CALL_USER_ID']: 0,
				"portalCallData" => $params['PORTAL_CALL'] == 'Y'? $params['PORTAL_CALL_DATA']: Array(),
				"config" => $params['CONFIG']? $params['CONFIG']: Array(),
				"lineNumber" => $params['PORTAL_NUMBER'] ?: '',
				"lineName" => $params['PORTAL_NUMBER_NAME'] ?: '',
				"CRM" => $params['CRM']? $params['CRM']: Array(),
			);

			if(!$config['portalCall'])
			{
				$config["showCrmCard"] = ($call->isCrmEnabled());
				$config["crmEntityType"] = $call->getPrimaryEntityType();
				$config["crmEntityId"] = $call->getPrimaryEntityId();
				$config["crmBindings"] = CVoxImplantCrmHelper::resolveBindingNames($call->getCrmBindings());
			}

			$push['send_immediately'] = 'Y';
			$push['advanced_params'] = Array(
				"notificationsToCancel" => array('VI_CALL_'.$params['CALL_ID']),
			);
		}
		else if ($params['COMMAND'] == 'timeout')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
				"failedCode" => intval($params['FAILED_CODE']),
			);
			$push['send_immediately'] = 'Y';
			$push['advanced_params'] = Array(
				"notificationsToCancel" => array('VI_CALL_'.$params['CALL_ID']),
			);
		}

		if (isset($params['MARK']))
		{
			$config['mark'] = $params['MARK'];
		}

		\Bitrix\Pull\Event::add($params['USER_ID'],
			Array(
				'module_id' => 'voximplant',
				'command' => $params['COMMAND'],
				'params' => $config,
				'push' => $push
			)
		);

		return true;
	}

	public static function StartCall($userId, $phoneNumber, $params)
	{
		$phoneNormalized = CVoxImplantPhone::Normalize($phoneNumber);
		if (!$phoneNormalized)
		{
			$phoneNormalized = preg_replace("/[^0-9\#\*]/i", "", $phoneNumber);
		}

		$userId = intval($userId);
		if ($userId <= 0 || !$phoneNormalized)
			return false;

		$additionalParams = array();
		if(isset($params['LINE_ID']))
		{
			$additionalParams['LINE_ID'] = $params['LINE_ID'];
		}

		$viHttp = new CVoxImplantHttp();
		$result = $viHttp->StartOutgoingCall($userId, $phoneNumber, $additionalParams);
		if(!$result)
		{
			return array(
				'ERROR' => $viHttp->GetError()->msg
			);
		}

		$config = self::GetConfigByUserId($userId);
		$callFields = array(
			'CALL_ID' =>  $result->call_id,
			'CONFIG_ID' => $config['ID'],
			'USER_ID' => $userId,
			'INCOMING' => CVoxImplantMain::CALL_OUTGOING,
			'CALLER_ID' => $phoneNormalized,
			'ACCESS_URL' => $result->access_url,
			'STATUS' => VI\Model\CallTable::STATUS_WAITING,
			'DATE_CREATE' => new FieldType\DateTime(),
		);

		if(isset($params['SRC_ACTIVITY_ID']))
		{
			$callFields['CRM_ACTIVITY_ID'] = $params['SRC_ACTIVITY_ID'];
		}
		if(isset($params['CALL_LIST_ID']))
		{
			$callFields['CRM_CALL_LIST'] = $params['CALL_LIST_ID'];
		}

		$call = VI\Call::create($callFields);
		$call->addUsers([$userId], VI\Model\CallUserTable::ROLE_CALLEE, VI\Model\CallUserTable::STATUS_INVITING);

		$crmEntities = [];
		if(isset($params['ENTITY_TYPE']) && isset($params['ENTITY_ID']) && strpos($params['ENTITY_TYPE'], 'CRM_') === 0)
		{
			$crmEntities[] = [
				'ENTITY_TYPE' => substr($params['ENTITY_TYPE'], 4),
				'ENTITY_ID' => $params['ENTITY_ID'],
				'IS_PRIMARY' => 'Y',
				'IS_CREATED' => 'N'
			];
		}
		if(isset($params['ENTITY_TYPE_NAME']) && isset($params['ENTITY_ID']))
		{
			$crmEntities[] = [
				'ENTITY_TYPE' => $params['ENTITY_TYPE_NAME'],
				'ENTITY_ID' => $params['ENTITY_ID'],
				'IS_PRIMARY' => 'Y',
				'IS_CREATED' => 'N'
			];
		}
		$call->updateCrmEntities($crmEntities);

		return array(
			'USER_ID' => $userId,
			'PHONE_NUMBER' => $phoneNormalized,
			'CALL_ID' => $call->getCallId(),
			'CALL_DEVICE' => 'PHONE',
			'EXTERNAL' => true,
			'CONFIG' => CVoxImplantConfig::getConfigForPopup($call->getCallId()),
		);
	}

	/**
	 * Initiates infocall with a text to say.
	 * @param string $outputNumber Id of the line to perform outgoing call.
	 * @param string $number Number to be called.
	 * @param string $text Text to say.
	 * @param string $voiceLanguage TTS voice (@see: Tts\Language).
	 * @param string $voiceSpeed TTS voice speed (@see Tts\Speed).
	 * @param string $voiceVolume TTS voice volume (@see Tts\Volume).
	 * @return Result Returns array with CALL_ID or error.
	 */
	public static function StartInfoCallWithText($outputNumber, $number, $text, $voiceLanguage = '', $voiceSpeed = '', $voiceVolume = '')
	{
		$result = new Result();
		CVoxImplantHistory::WriteToLog(Array($outputNumber, $number, $text, $voiceLanguage, $voiceSpeed, $voiceVolume), 'StartInfoCallWithText');

		if ($outputNumber === CVoxImplantConfig::LINK_BASE_NUMBER)
		{
			$result->addError(new Error('Making infocall using LINK_BASE_NUMBER is not allowed'));
			return $result;
		}

		$numberConfig = CVoxImplantConfig::GetConfigBySearchId($outputNumber);
		if (isset($numberConfig['ERROR']))
		{
			$result->addError(new Error('Could not find config for number '.$outputNumber));
			return $result;
		}

		$limitRemainder = VI\Limits::getInfocallsLimitRemainder($numberConfig['PORTAL_MODE']);
		if($limitRemainder === 0)
		{
			$result->addError(new Error('Infocall limit for this month is exceeded'));
			return $result;
		}

		if($numberConfig['PORTAL_MODE'] === CVoxImplantConfig::MODE_SIP)
			$phoneNormalized = $number;
		else
			$phoneNormalized = CVoxImplantPhone::Normalize($number);

		if (!$phoneNormalized)
		{
			$result->addError(new Error('Phone number is not correct'));
			return $result;
		}

		$voiceLanguage = $voiceLanguage ?: Tts\Language::getDefaultVoice(\Bitrix\Main\Context::getCurrent()->getLanguage());
		$voiceSpeed = $voiceSpeed ?: Tts\Speed::getDefault();
		$voiceVolume = $voiceVolume ?: Tts\Volume::getDefault();

		$options = array(
			'MODE' => self::INFOCALL_MODE_TEXT,
			'VOICE_LANGUAGE' => $voiceLanguage,
			'VOICE_SPEED' => $voiceSpeed,
			'VOICE_VOLUME' => $voiceVolume
		);

		$httpClient = new CVoxImplantHttp();
		$infoCallResult = $httpClient->StartInfoCall($phoneNormalized, $text, $options, $numberConfig);

		if($infoCallResult === false)
		{
			$result->addError(new Error('Infocall failure'));
			return $result;
		}

		CVoxImplantHistory::WriteToLog($result, 'Infocall started');
		if($limitRemainder > 0)
		{
			VI\Limits::addInfocall($numberConfig['PORTAL_MODE']);
		}
		$result->setData(array(
			'CALL_ID' => $infoCallResult->call_id
		));

		return $result;
	}

	/**
	 * Initiates infocall with mp3 to play
	 * @param string $outputNumber Id of the line to perform outgoing call.
	 * @param string $number Number to be called.
	 * @param string $url Url of the mp3 to play.
	 * @return Result Returns array with CALL_ID or error.
	 */
	public static function StartInfoCallWithSound($outputNumber, $number, $url)
	{
		$result = new Result();
		CVoxImplantHistory::WriteToLog(Array($outputNumber, $number, $url), 'StartInfoCallWithSound');

		if($outputNumber === CVoxImplantConfig::LINK_BASE_NUMBER)
		{
			$result->addError(new Error('Making infocall using LINK_BASE_NUMBER is not allowed'));
			return $result;
		}

		$numberConfig = CVoxImplantConfig::GetConfigBySearchId($outputNumber);
		if(isset($numberConfig['ERROR']))
		{
			$result->addError(new Error('Could not find config for number ' . $outputNumber));
			return $result;
		}

		$limitRemainder = VI\Limits::getInfocallsLimitRemainder($numberConfig['PORTAL_MODE']);
		if ($limitRemainder === 0)
		{
			$result->addError(new Error('Infocall limit for this month is exceeded'));
			return $result;
		}

		if($numberConfig['PORTAL_MODE'] === CVoxImplantConfig::MODE_SIP)
			$phoneNormalized = $number;
		else
			$phoneNormalized = CVoxImplantPhone::Normalize($number);

		if (!$phoneNormalized)
		{
			$result->addError(new Error('Phone number is not correct'));
			return $result;
		}

		$options = array(
			'MODE' => self::INFOCALL_MODE_URL,
		);

		$httpClient = new CVoxImplantHttp();
		$infocallResult = $httpClient->StartInfoCall($phoneNormalized, $url, $options, $numberConfig);

		if($infocallResult === false)
		{
			$result->addError(new Error('Infocall failure'));
			return $result;
		}

		CVoxImplantHistory::WriteToLog($result, 'Infocall started');
		if($limitRemainder > 0)
		{
			VI\Limits::addInfocall($numberConfig['PORTAL_MODE']);
		}
		$result->setData(array(
			'CALL_ID' => $infocallResult->call_id
		));
		return $result;
	}

	/**
	 * Initiates 'callback' call
	 * @param string $lineSearchId SearchId of the line to perform outgoing call.
	 * @param string $number Number to be called to.
	 * @param string $textToPronounce Entry text to be pronounced to the manager.
	 * @param string $voice Id of the voice to pronounce entry text. @see Language::getList.
	 * @param array $customData Additional fields to be passed to the scenario.
	 * @param int $redialAttempt Redial attempt.
	 * @return Result Returns array with CALL_ID in case of success or error.
	 */
	public static function startCallBack($lineSearchId, $number, $textToPronounce, $voice = '', array $customData = array(), $redialAttempt = 0)
	{
		$result = new Result();
		CVoxImplantHistory::WriteToLog(Array($lineSearchId, $number, $textToPronounce, $voice), 'startCallBack');

		$line = CVoxImplantConfig::GetLine($lineSearchId);
		if(!$line)
		{
			$result->addError(new Error('Could not find line '.$lineSearchId));
			return $result;
		}

		if($line['TYPE'] === 'REST')
		{
			$lineNumber = substr($line['LINE_NUMBER'], 0, 8) === 'REST_APP' ? '' : $line['LINE_NUMBER'];
			$restAppParams = $customData;
			$restAppParams['APP_ID'] = $line['REST_APP_ID'];
			$restAppParams['LINE_NUMBER'] = $lineNumber;
			$restAppParams['PHONE_NUMBER'] = $number;
			$restAppParams['TEXT'] = $textToPronounce;
			$restAppParams['VOICE'] = $voice;
			VI\Rest\Helper::startCallBack($restAppParams);
			return $result;
		}

		$numberConfig = CVoxImplantConfig::GetConfigBySearchId($lineSearchId);
		if (isset($numberConfig['ERROR']))
		{
			$result->addError(new Error('Could not find config for number '.$lineSearchId));
			return $result;
		}

		$phoneNormalized = CVoxImplantPhone::Normalize($number, 0);
		if (!$phoneNormalized)
		{
			$result->addError(new Error('Phone number is not correct'));
			return $result;
		}

		$callFields = array(
			'CONFIG_ID' => $numberConfig['ID'],
			'CALLER_ID' => $phoneNormalized,
			'STATUS' => VI\Model\CallTable::STATUS_CONNECTING,
			'DATE_CREATE' => new FieldType\DateTime(),
			'INCOMING' => CVoxImplantMain::CALL_CALLBACK,
			'CALLBACK_PARAMETERS' => array(
				'lineSearchId' => $lineSearchId,
				'number' => $number,
				'textToPronounce' => $textToPronounce,
				'voice' => $voice,
				'customData' => $customData,
				'redialAttempt' => $redialAttempt,
			),
		);

		if(isset($customData['CRM_ENTITY_TYPE']) && isset($customData['CRM_ENTITY_ID']))
		{
			$callFields['CRM_ENTITY_TYPE'] = $customData['CRM_ENTITY_TYPE'];
			$callFields['CRM_ENTITY_ID'] = $customData['CRM_ENTITY_ID'];
		}

		$voice = $voice ?: Tts\Language::getDefaultVoice(\Bitrix\Main\Context::getCurrent()->getLanguage());
		$viHttp = new CVoxImplantHttp();
		$callBackResult = $viHttp->StartCallBack($lineSearchId, $phoneNormalized, $textToPronounce, $voice);

		if($callBackResult === false)
		{
			$result->addError(new Error($viHttp->GetError()->msg, $viHttp->GetError()->code));
		}
		else
		{
			$callId = $callBackResult->call_id;
			$callFields['CALL_ID'] = $callId;
			$call = VI\Call::create($callFields);

			$result->setData(array(
				'CALL_ID' => $call->getCallId()
			));
		}

		return $result;
	}

	/**
	 * This function is intended for repeating missed callbacks, and should be used as an agent. All parameters are the same, as in startCallback.
	 * @param array $parameters Callback parameters, as saved by previous call to startCallback
	 * @return string
	 */
	public static function restartCallback(array $parameters)
	{
		$result = self::startCallBack(
			$parameters['lineSearchId'],
			$parameters['number'],
			$parameters['textToPronounce'],
			$parameters['voice'] ?: '',
			is_array($parameters['customData']) ? $parameters['customData'] : array(),
			(int)$parameters['redialAttempt'] + 1
		);

		if($result->isSuccess())
		{
			CVoxImplantHistory::WriteToLog('Callback restarted successfully');
			return true;
		}
		else
		{
			CVoxImplantHistory::WriteToLog('There were errors during callback restart: ' . implode('; ', $result->getErrorMessages()));
			return false;
		}
	}

	/**
	 * Returns handler of the special number if dialed number should be handled specially. If dialed number should be handler as usual, returns false.
	 * @param string $phoneNumber Phone Number
	 * @return Bitrix\Voximplant\Special\Action | false
	 */
	public static function getSpecialNumberHandler($phoneNumber)
	{
		$specialHandlers = array(
			VI\Special\Action\Intercept::getClass()
		);

		foreach ($specialHandlers as $specialHandlerClass)
		{
			$specialHandler = new $specialHandlerClass();
			if ($specialHandler->checkPhoneNumber($phoneNumber))
				return $specialHandler;
		}
		return false;
	}
}
