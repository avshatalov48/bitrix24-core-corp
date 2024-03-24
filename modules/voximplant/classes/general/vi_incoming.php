<?php
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Type as FieldType;
use Bitrix\Voximplant as VI;
use Bitrix\Main\Web\Json;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\DB\SqlExpression;

class CVoxImplantIncoming
{
	const RULE_WAIT = 'wait';
	const RULE_TALK = 'talk';
	const RULE_HUNGUP = 'hungup';
	const RULE_PSTN = 'pstn';
	const RULE_PSTN_SPECIFIC = 'pstn_specific';
	const RULE_USER = 'user';
	const RULE_VOICEMAIL = 'voicemail';
	const RULE_QUEUE = 'queue';
	const RULE_NEXT_QUEUE = 'next_queue';

	const COMMAND_INVITE = 'invite';
	const COMMAND_ENQUEUE = 'enqueue';
	const COMMAND_DEQUEUE = 'dequeue';
	const COMMAND_BUSY = 'busy';
	const COMMAND_INTERCEPT = 'interceptCall';

	const TYPE_CONNECT_SIP = 'sip';
	const TYPE_CONNECT_DIRECT = 'direct';
	const TYPE_CONNECT_CRM = 'crm';
	const TYPE_CONNECT_QUEUE = 'queue';
	const TYPE_CONNECT_CONFIG = 'config';
	const TYPE_CONNECT_USER = 'user';
	const TYPE_CONNECT_IVR = 'ivr';

	/**
	 * Returns incoming call scenario configuration.
	 * @param array $params Array of parameters.
	 * 	<li> PHONE_NUMBER - search id of the portal's line.
	 * @return array
	 */
	public static function GetConfig($params)
	{
		if (!VI\Limits::canCall())
		{
			return [
				'error' => ['code' => 'PAID_PLAN_REQUIRED']
			];
		}
		$result = CVoxImplantConfig::GetConfigBySearchId($params['PHONE_NUMBER']);

		if(!$result['ID'])
		{
			return $result;
		}

		$result['TYPE_CONNECT'] = self::TYPE_CONNECT_CONFIG;
		if($result["PORTAL_MODE"] == CVoxImplantConfig::MODE_GROUP)
		{
			$result["SEARCH_ID"] = $params["PHONE_NUMBER"];
		}
		$result = CVoxImplantIncoming::RegisterCall($result, $params);

		$isNumberInBlacklist = CVoxImplantIncoming::IsNumberInBlackList($params["CALLER_ID"], $result['NUMBER_COUNTRY_CODE']);
		$isBlacklistAutoEnable = Bitrix\Main\Config\Option::get("voximplant", "blacklist_auto", "N") === "Y";

		if ($result["WORKTIME_SKIP_CALL"] === "Y" && !$isNumberInBlacklist && $isBlacklistAutoEnable)
		{
			$isNumberInBlacklist = CVoxImplantIncoming::CheckNumberForBlackList($params["CALLER_ID"]);
		}

		if ($isNumberInBlacklist)
		{
			$result["NUMBER_IN_BLACKLIST"] = "Y";
		}

		if (!VI\Limits::canSelectCallSource())
		{
			$result["CRM_SOURCE"] = 'CALL';
		}
		if (!VI\Limits::canVote())
		{
			$result["CALL_VOTE"] = 'N';
		}
		if ($result["QUEUE_TYPE"] === CVoxImplantConfig::QUEUE_TYPE_ALL && !VI\Limits::isQueueAllAllowed())
		{
			$result["QUEUE_TYPE"] = CVoxImplantConfig::QUEUE_TYPE_EVENLY;
		}
		if ($result["NO_ANSWER_RULE"] === self::RULE_NEXT_QUEUE && !VI\Limits::isRedirectToQueueAllowed())
		{
			$result["NO_ANSWER_RULE"] = self::RULE_VOICEMAIL;
		}

		foreach(GetModuleEvents("voximplant", "onCallInit", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, Array(Array(
				'CALL_ID' => $params['CALL_ID'],
				'CALL_TYPE' => CVoxImplantMain::CALL_INCOMING,
				'ACCOUNT_SEARCH_ID' => $params['ACCOUNT_SEARCH_ID'],
				'PHONE_NUMBER' => $params['PHONE_NUMBER'],
				'CALLER_ID' => $params['CALLER_ID'],
			)));
		}

		return $result;
	}

	public static function SendPullEvent($params)
	{
		// TODO check $params
		if (!CModule::IncludeModule('pull') || !CPullOptions::GetQueueServerStatus() || $params['USER_ID'] <= 0)
			return false;

		$config = Array();
		$push = Array();
		$callId = $params['CALL_ID'];
		if ($params['COMMAND'] == 'invite')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
				"callerId" => $params['CALLER_ID'],
				"phoneNumber" => $params['PHONE_NAME'],
				"chatId" => 0,
				"chat" => array(),
				"typeConnect" => $params['TYPE_CONNECT'],
				"portalCall" => $params['PORTAL_CALL'] == 'Y'? true: false,
				"portalCallUserId" => $params['PORTAL_CALL'] == 'Y'? $params['PORTAL_CALL_USER_ID']: 0,
				"portalCallData" => $params['PORTAL_CALL'] == 'Y'? $params['PORTAL_CALL_DATA']: Array(),
				"config" => $params['CONFIG']? $params['CONFIG']: Array(),
				"CRM" => $params['CRM'],
				"isCallback" => $params['CALLBACK_MODE']
			);

			$callName = $params['CALLER_ID'];
			if (isset($params['CRM']['CONTACT']['NAME']) && $params['CRM']['CONTACT']['NAME'] <> '')
			{
				$callName = $params['CRM']['CONTACT']['NAME'];
			}
			if (isset($params['CRM']['COMPANY']) && $params['CRM']['COMPANY'] <> '')
			{
				$callName .= ' ('.$params['CRM']['COMPANY'].')';
			}
			else if (isset($params['CRM']['CONTACT']['POST']) && $params['CRM']['CONTACT']['POST'] <> '')
			{
				$callName .= ' ('.$params['CRM']['CONTACT']['POST'].')';
			}

			$push['sub_tag'] = 'VI_CALL_'.$params['CALL_ID'];
			$push['send_immediately'] = 'Y';
			$push['sound'] = 'call.aif';
			$push['advanced_params'] = Array(
				"notificationsToCancel" => array('VI_CALL_'.$params['CALL_ID']),
				"androidHighPriority" => true,
			);
			if ($params['PORTAL_CALL'] == 'Y')
			{
				$push['message'] = GetMessage('INCOMING_CALL', Array('#NAME#' => $params['PORTAL_CALL_DATA']['users'][$params['PORTAL_CALL_USER_ID']]['name']));
			}
			else
			{
				$push['message'] = GetMessage('INCOMING_CALL', Array('#NAME#' => $callName));
				$push['message'] = $push['message'].' '.GetMessage('CALL_FOR_NUMBER', Array('#NUMBER#' => $params['PHONE_NAME']));
			}
			$push['params'] = Array(
				'ACTION' => 'VI_CALL_'.$params['CALL_ID'],
				'PARAMS' => $config
			);
		}
		else if ($params['COMMAND'] == 'update_crm')
		{
			$call = VI\Model\CallTable::getByCallId($callId);
			$config = Array(
				"callId" => $params['CALL_ID'],
				"CRM" => $params['CRM'],
			);
			if(is_array($call))
			{
				$config["showCrmCard"] = ($call['CRM'] == 'Y');
				$config["crmEntityType"] = $call['CRM_ENTITY_TYPE'];
				$config["crmEntityId"] = $call['CRM_ENTITY_ID'];
				$config["crmActivityId"] = $call['CRM_ACTIVITY_ID'];
				$config["crmActivityEditUrl"] = CVoxImplantCrmHelper::getActivityEditUrl($call['CRM_ACTIVITY_ID']);
			}
		}
		else if ($params['COMMAND'] == 'timeout' || $params['COMMAND'] == 'answer_self')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
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
		$userIds = is_array($params['USER_ID']) ? $params['USER_ID'] : array($params['USER_ID']);
		\Bitrix\Pull\Event::add($userIds,
			Array(
				'module_id' => 'voximplant',
				'command' => $params['COMMAND'],
				'params' => $config,
				'push' => $push
			)
		);

		return true;
	}

	public static function SendCommand($params, $waitResponse = false)
	{
		// TODO check $params
		$result = new \Bitrix\Main\Result();
		$call = VI\Model\CallTable::getByCallId($params['CALL_ID']);
		if (!$call)
		{
			$result->addError(new \Bitrix\Main\Error('Call not found', 'NOT_FOUND'));
			return $result;
		}

		global $USER;

		$answer['COMMAND'] = $params['COMMAND'];
		$answer['OPERATOR_ID'] = $params['OPERATOR_ID'] ?? $USER->GetId();
		if ($params['COMMAND'] == CVoxImplantIncoming::COMMAND_INVITE)
		{
		}
		else if ($params['COMMAND'] == CVoxImplantIncoming::RULE_WAIT)
		{
		}
		else if ($params['COMMAND'] == CVoxImplantIncoming::RULE_QUEUE)
		{
		}
		else if ($params['COMMAND'] == CVoxImplantIncoming::RULE_USER)
		{
			$answer['USER_ID'] = intval($params['USER_ID']);
		}
		else if ($params['COMMAND'] == CVoxImplantIncoming::RULE_VOICEMAIL)
		{
			$answer['USER_ID'] = intval($params['USER_ID']);
		}
		else if ($params['COMMAND'] == CVoxImplantIncoming::COMMAND_BUSY)
		{
		}
		else if ($params['COMMAND'] == CVoxImplantIncoming::COMMAND_DEQUEUE)
		{
			$answer['OPERATOR'] = $params['OPERATOR'];
		}
		else if ($params['COMMAND'] == CVoxImplantIncoming::COMMAND_INTERCEPT)
		{
			$answer['OPERATOR'] = $params['OPERATOR'];
		}
		else
		{
			$answer['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
		}

		if(isset($params['DEBUG_INFO']))
		{
			$answer['DEBUG_INFO'] = $params['DEBUG_INFO'];
		}
		$answer['CALL_ID'] = $params['CALL_ID'];

		$http = VI\HttpClientFactory::create(array(
			'waitResponse' => $waitResponse
		));
		$queryResult = $http->query('POST', $call['ACCESS_URL'], Json::encode($answer));
		if($waitResponse)
		{
			if ($queryResult === false)
			{
				$httpClientErrors = $http->getError();
				if(!empty($httpClientErrors))
				{
					foreach ($httpClientErrors as $code => $message)
					{
						$result->addError(new \Bitrix\Main\Error($message, $code));
					}
				}
			}

			$responseStatus = $http->getStatus();
			if ($responseStatus == 200)
			{
				// nothing here
			}
			else if ($http->getStatus() == 404)
			{
				$result->addError(new \Bitrix\Main\Error('Call scenario is not running', 'NOT_FOUND'));
			}
			else
			{
				$result->addError(new \Bitrix\Main\Error("Scenario server returns code " . $http->getStatus()));

			}
		}

		return $result;
	}

	public static function Answer($callId)
	{
		$res = VI\Model\CallTable::getList(Array(
			'select' => Array('ID', 'ACCESS_URL'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		global $USER;

		$ViMain = new CVoxImplantMain($USER->GetId());
		$result = $ViMain->GetDialogInfo($_POST['NUMBER']);

		if ($result)
		{
			echo CUtil::PhpToJsObject(Array(
				'DIALOG_ID' => $result['DIALOG_ID'],
				'HR_PHOTO' => $result['HR_PHOTO'],
				'ERROR' => ''
			));
		}
		else
		{
			echo CUtil::PhpToJsObject(Array(
				'CODE' => $ViMain->GetError()->code,
				'ERROR' => $ViMain->GetError()->msg
			));
		}
	}

	public static function RegisterCall($config, $params)
	{
		$call = VI\Call::load($params['CALL_ID']);

		$portalNumber = $config['SEARCH_ID'];
		$externalLineId = null;

		if($config['PORTAL_MODE'] === CVoxImplantConfig::MODE_SIP && $config['SIP_DETECT_LINE_NUMBER'] === 'Y' && is_array($params['SIP_HEADERS']))
		{
			// try to guess portal number from sip headers
			$portalNumber = static::guessPortalNumber($config, $params['SIP_HEADERS']);

			if($portalNumber !== $config['SEARCH_ID'])
			{
				$normalizedNumber = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($portalNumber)->format(\Bitrix\Main\PhoneNumber\Format::E164);
				VI\Model\ExternalLineTable::merge([
					'TYPE' => VI\Model\ExternalLineTable::TYPE_SIP,
					'NUMBER' => $portalNumber,
					'NORMALIZED_NUMBER' => $normalizedNumber,
					'SIP_ID' => $config['SIP_ID'],
					'IS_MANUAL' => 'N'
				]);

				$row = VI\Model\ExternalLineTable::getRow([
					'filter' => [
						'=SIP_ID' => $config['SIP_ID'],
						'=NUMBER' => $portalNumber
					]
				]);
				if($row)
				{
					$externalLineId = $row['ID'];
				}
			}
		}

		if($call)
		{
			// callback calls are pre-created
			$callFields = [
				'STATUS' => VI\Model\CallTable::STATUS_WAITING,
				//'CRM' => $config['CRM'],
				'ACCESS_URL' => $params['ACCESS_URL'],
				'DATE_CREATE' => new Bitrix\Main\Type\DateTime(),
				'WORKTIME_SKIPPED' => $config['WORKTIME_SKIP_CALL'] == 'Y',
				'PORTAL_NUMBER' => $config['SEARCH_ID'],
				'SESSION_ID' => $params['SESSION_ID'],
			];
			$call->update($callFields);
		}
		else
		{
			$call = VI\Call::create([
				'CONFIG_ID' => $config['ID'],
				'CALL_ID' => $params['CALL_ID'],
				'USER_ID' => 0,
				'CALLER_ID' => $params['CALLER_ID'],
				'STATUS' => VI\Model\CallTable::STATUS_WAITING,
				'INCOMING' => CVoxImplantMain::CALL_INCOMING,
				//'CRM' => $config['CRM'],
				'ACCESS_URL' => $params['ACCESS_URL'],
				'DATE_CREATE' => new Bitrix\Main\Type\DateTime(),
				'WORKTIME_SKIPPED' => $config['WORKTIME_SKIP_CALL'] == 'Y',
				'PORTAL_NUMBER' => $portalNumber,
				'EXTERNAL_LINE_ID' => $externalLineId,
				'SESSION_ID' => $params['SESSION_ID'],
				'SIP_HEADERS' => is_array($params['SIP_HEADERS']) ? $params['SIP_HEADERS'] : [],
				'LAST_PING'=> null,
				'QUEUE_ID' => null,
			]);
		}

		//if ($config['CRM'] == 'Y')
		$crmData = CVoxImplantCrmHelper::getCrmEntities($call);
		$call->updateCrmEntities($crmData);
		$activityBindings = CVoxImplantCrmHelper::getActivityBindings($call);
		if(is_array($activityBindings))
		{
			$call->updateCrmBindings($activityBindings);
		}
		if(\CVoxImplantConfig::GetLeadWorkflowExecution() == \CVoxImplantConfig::WORKFLOW_START_IMMEDIATE)
		{
			CVoxImplantCrmHelper::StartCallTrigger($call);
		}

		if ($config['WORKTIME_SKIP_CALL'] == 'Y')
		{
			$config['WORKTIME_USER_ID'] = 0;
			if($call->getPrimaryEntityId() > 0 && $call->getPrimaryEntityType() != '')
			{
				$config['WORKTIME_USER_ID'] = CVoxImplantCrmHelper::getResponsible($call->getPrimaryEntityType(), $call->getPrimaryEntityId());
			}
			else
			{
				$queue =  VI\Queue::createWithId($config['QUEUE_ID']);
				$queueUserId = ($queue instanceof VI\Queue) ?$queue->getFirstUserId($config['TIMEMAN'] == 'Y'): false;

				if ($queueUserId)
				{
					$queue->touchUser($queueUserId);
					$config['WORKTIME_USER_ID'] = $queueUserId;
				}
			}

			if($config['WORKTIME_USER_ID'] > 0)
			{
				$call->updateUserId($config['WORKTIME_USER_ID']);
				CVoxImplantCrmHelper::registerCallInCrm($call);

				if(\CVoxImplantConfig::GetLeadWorkflowExecution() == \CVoxImplantConfig::WORKFLOW_START_IMMEDIATE)
				{
					CVoxImplantCrmHelper::StartCallTrigger($call, true);
				}
			}
			else
			{
				$queue = VI\Queue::createWithId($config['QUEUE_ID']);
				$queueUserId = ($queue instanceof VI\Queue) ? $queue->getFirstUserId($config['TIMEMAN'] == 'Y') : false;
				if($queueUserId)
				{
					$queue->touchUser($queueUserId);
					$config['WORKTIME_USER_ID'] = $queueUserId;
				}
			}
		}

		return $config;
	}

	public static function IsNumberInBlackList($number, $countryCode = null)
	{
		$numberE164 = \Bitrix\Main\PhoneNumber\Parser::getInstance()
			->parse($number, $countryCode)
			->format(\Bitrix\Main\PhoneNumber\Format::E164);

		$numberStripped = CVoxImplantPhone::stripLetters($number);
		$dbBlacklist = VI\BlacklistTable::getList(
			[
				"select" => ["ID"],
				"filter" => [
					"LOGIC" => "OR",
					"=NUMBER_E164" => $numberE164,
					"=NUMBER_STRIPPED" => $numberStripped
				]
			]
		);
		if ($dbBlacklist->fetch())
		{
			return true;
		}

		return false;
	}

	public static function CheckNumberForBlackList($number)
	{
		$blackListTime = Bitrix\Main\Config\Option::get("voximplant", "blacklist_time", 5);
		$blackListCount = Bitrix\Main\Config\Option::get("voximplant", "blacklist_count", 5);

		$minTime = new Bitrix\Main\Type\DateTime();
		$minTime->add('-'.$blackListTime.' minutes');

		$dbData = VI\StatisticTable::getList(array(
			'filter' => array(
				"PHONE_NUMBER" => $number,
				'>CALL_START_DATE' => $minTime,
			),
			'select' => array('ID')
		));

		$callsCount = 0;
		while($dbData->fetch())
		{
			$callsCount++;
			if ($callsCount >= $blackListCount)
			{
				$number = mb_substr($number, 0, 20);
				VI\BlacklistTable::add(array(
					"PHONE_NUMBER" => $number
				));

				$messageUserId = Bitrix\Main\Config\Option::get("voximplant", "blacklist_user_id", "");
				CVoxImplantHistory::SendMessageToChat(
					$messageUserId,
					$number,
					CVoxImplantMain::CALL_INCOMING,
					GetMessage("BLACKLIST_NUMBER")
				);

				return true;
			}
		}

		return false;
	}

	/**
	 * @param VI\Call $phoneNumber
	 * @param bool $checkTimeman
	 * @return array|false
	 */
	public static function getCrmResponsible(VI\Call $call, $checkTimeman = false)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return false;
		}

		$entityManager = VI\Integration\Crm\EntityManagerRegistry::getWithCall($call);
		if(!$entityManager)
		{
			return false;
		}

		$responsibleId = $entityManager->getPrimaryAssignedById();
		if(!$responsibleId)
		{
			return false;
		}
		$result = self::getUserInfo($responsibleId, $checkTimeman);
		if(is_array($result))
		{
			$result['CRM_ENTITY_TYPE'] = CCrmOwnerType::ResolveName($entityManager->getPrimaryTypeId());
			$result['CRM_ENTITY_ID'] = $entityManager->getPrimaryId();
		}

		return $result;
	}

	public static function getByInternalPhoneNumber(string $phoneNumber, $checkTimeman = false): ?array
	{
		$query = \Bitrix\Voximplant\Model\UserTable::query();
		$query
			->addSelect(new ExpressionField('ENTITY_TYPE', new SqlExpression('?s', 'user')))
			->addSelect('ID', 'ENTITY_ID')
			->addSelect('IS_ONLINE')
			->addSelect('IS_BUSY')
			->addSelect('UF_VI_PHONE')
			->where('UF_PHONE_INNER', $phoneNumber)
			->where('ACTIVE', 'Y')
		;

		$query2 = VI\Model\QueueTable::query();
		$query2
			->addSelect(new ExpressionField('ENTITY_TYPE', new SqlExpression('?s', 'queue')))
			->addSelect('ID', 'ENTITY_ID')
			->addSelect(new ExpressionField('IS_ONLINE', new SqlExpression('?s', 'Y')))
			->addSelect(new ExpressionField('IS_BUSY', new SqlExpression('?s', 'N')))
			->addSelect(new ExpressionField('UF_VI_PHONE', new SqlExpression('?s', 'N')))
			->where('PHONE_NUMBER', $phoneNumber)
		;
		$query->unionAll($query2);

		$row = $query->fetch();
		if (!$row)
		{
			return null;
		}

		$result = [
			'ENTITY_TYPE' => $row['ENTITY_TYPE'],
			'ENTITY_ID' => (int)$row['ENTITY_ID']
		];

		if ($row['ENTITY_TYPE'] === 'user')
		{
			$skipByTimeman = false;
			if ($checkTimeman)
			{
				$skipByTimeman = !CVoxImplantUser::GetActiveStatusByTimeman($row['ENTITY_ID']);
			}

			$result['USER_DATA'] = [
				'USER_HAVE_PHONE' => $row['UF_VI_PHONE'] === 'Y' ? 'Y' : 'N',
				'USER_HAVE_MOBILE' => CVoxImplantUser::hasMobile($row['ENTITY_ID']) ? 'Y' : 'N',
				'ONLINE' => $row['IS_ONLINE'],
				'BUSY' => $row['IS_BUSY'],
				'AVAILABLE' => (!$skipByTimeman && ($row['IS_BUSY'] !== 'Y') && ($row['IS_ONLINE'] === 'Y' || $row['UF_VI_PHONE'] === 'Y' || $row['USER_HAVE_MOBILE'] === 'Y')) ? 'Y' : 'N',
			];
		}
		return $result;
	}

	public static function getUserByDirectCode($directCode, $checkTimeman = false)
	{
		$userData = \Bitrix\Voximplant\Model\UserTable::getList(Array(
			'select' => Array('ID', 'IS_ONLINE', 'IS_BUSY', 'UF_VI_PHONE', 'ACTIVE'),
			'filter' => Array('=UF_PHONE_INNER' => $directCode, '=ACTIVE' => 'Y'),
		))->fetch();
		if (!$userData)
			return false;

		$userId = $userData['ID'];

		$skipByTimeman = false;
		if ($checkTimeman)
		{
			$skipByTimeman = !CVoxImplantUser::GetActiveStatusByTimeman($userId);
		}

		$result = array(
			'USER_ID' => $userData['ID'],
			'USER_HAVE_PHONE' => $userData['UF_VI_PHONE'] == 'Y' ? 'Y' : 'N',
			'USER_HAVE_MOBILE' => CVoxImplantUser::hasMobile($userId) ? 'Y' : 'N',
			'ONLINE' => $userData['IS_ONLINE'],
			'BUSY' => $userData['IS_BUSY'],
			'AVAILABLE' => (!$skipByTimeman && ($userData['IS_BUSY'] != 'Y') && ($userData['IS_ONLINE'] == 'Y' || $userData['UF_VI_PHONE'] == 'Y' || $userData['USER_HAVE_MOBILE'] == 'Y')) ? 'Y' : 'N',
		);

		return $result;
	}

	/**
	 * @param $userId
	 * @param bool $checkTimeman
	 * @return array|bool
	 */
	public static function getUserInfo($userId, $checkTimeman = false)
	{
		$userData = \Bitrix\Voximplant\Model\UserTable::getList(Array(
			'select' => Array('ID', 'IS_ONLINE', 'IS_BUSY', 'UF_VI_PHONE', 'ACTIVE'),
			'filter' => Array('=ID' => $userId,  '=ACTIVE' => 'Y'),
		))->fetch();

		if (!$userData)
			return false;

		$skipByTimeman = false;
		if ($checkTimeman)
		{
			$skipByTimeman = !CVoxImplantUser::GetActiveStatusByTimeman($userId);
		}

		$userHasMobile = CVoxImplantUser::hasMobile($userId);

		$result = array(
			'USER_ID' => $userData['ID'],
			'USER_HAVE_PHONE' => $userData['UF_VI_PHONE'] == 'Y' ? 'Y' : 'N',
			'USER_HAVE_MOBILE' => $userHasMobile ? 'Y' : 'N',
			'ONLINE' => $userData['IS_ONLINE'],
			'BUSY' => $userData['IS_BUSY'],
			'AVAILABLE' => (!$skipByTimeman && ($userData['IS_BUSY'] != 'Y') && ($userData['IS_ONLINE'] == 'Y' || $userData['UF_VI_PHONE'] == 'Y' || $userHasMobile)) ? 'Y' : 'N',
		);

		return $result;
	}

	/**
	 * @param int $userId Id of the user.
	 * @param string $callId Id of the call.
	 */
	public static function interceptCall($userId, $callId)
	{
		$call = VI\Call::load($callId);
		if(!$call)
		{
			return false;
		}
		$call->moveToUser($userId);

		self::SendCommand(Array(
			'CALL_ID' => $callId,
			'COMMAND' => self::COMMAND_INTERCEPT,
			'USER_ID' => $userId,
			'OPERATOR' => self::getUserInfo($userId)
		));

		return true;
	}

	/**
	 * Finds call to intercept for the current user.
	 * @param int $userId Id of the user.
	 * @return string|false Returns id of the call or false if nothing found.
	 */
	public static function findCallToIntercept($userId)
	{
		$hourAgo = new FieldType\DateTime();
		$hourAgo->add('-1 hour');
		$userId = (int)$userId;

		$row = VI\Model\CallTable::getRow([
			'select' => [
				'CALL_ID'
			],
			'filter' => [
				'>DATE_CREATE' => $hourAgo,
				'=STATUS' => VI\Model\CallTable::STATUS_WAITING,
				[
					'LOGIC' => 'OR',
					[
						'=QUEUE.ALLOW_INTERCEPT' => 'Y',
						'=QUEUE.\Bitrix\Voximplant\Model\QueueUserTable:QUEUE.USER_ID' => $userId
					],
					[
						'INCOMING' => CVoxImplantMain::CALL_INCOMING,
						'@USER_ID' => new \Bitrix\Main\DB\SqlExpression("
							SELECT 
								QU.USER_ID 
							FROM 
								b_voximplant_queue_user QU
								JOIN b_voximplant_queue Q ON Q.ID = QU.QUEUE_ID
							WHERE
								Q.ALLOW_INTERCEPT='Y'
								AND EXISTS(SELECT 'X' FROM b_voximplant_queue_user QU2 WHERE QU2.QUEUE_ID = Q.ID AND QU2.USER_ID = $userId)
						")
					]
				]
			],
		]);

		return $row ? $row['CALL_ID'] : false;
	}

	public static function guessPortalNumber(array $config, array $sipHeaders)
	{
		$destination = '';
		$diversion = '';

		$sipUserPattern = '/(?>sip|tel):(\+?\d+)[@>]/';

		if(isset($sipHeaders['To']) && preg_match($sipUserPattern, $sipHeaders['To'], $matches))
		{
			$destination = $matches[1];
		}
		if(isset($sipHeaders['Diversion']) && preg_match($sipUserPattern, $sipHeaders['Diversion'], $matches))
		{
			$diversion = $matches[1];
		}

		if(!$diversion && !$destination)
		{
			return $config['SEARCH_ID'];
		}

		if($config['SIP_LINE_DETECT_HEADER_ORDER'] === CVoxImplantSip::HEADER_ORDER_DIVERSION_TO)
		{
			return $diversion ?: $destination;
		}
		else
		{
			return $destination ?: $diversion;
		}
	}
}
?>
