<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Result;
use Bitrix\Voximplant as VI;
use Bitrix\Main\Application;
use Bitrix\Main\IO;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;

class CVoxImplantHistory
{
	const DURATION_FORMAT_FULL = 'full';
	const DURATION_FORMAT_BRIEF = 'brief';
	const CALL_LOCK_PREFIX = 'vi_call';

	public static function Add($params)
	{
		$callId = (string)$params["CALL_ID"];
		if($callId == '')
		{
			CHTTP::SetStatus('400 Bad Request');
			return false;
		}

		$lockAcquired = static::getLock($callId);
		if(!$lockAcquired)
		{
			CHTTP::SetStatus('409 Conflict');
			return false;
		}

		$statisticRecord = VI\StatisticTable::getByCallId($params['CALL_ID']);
		if($statisticRecord)
		{
			self::WriteToLog('Duplicating statistic record, skipping');
			return false;
		}

		$call = VI\Call::load($params['CALL_ID']);
		if(!$call)
		{
			$call = static::recreateCall($params);
		}

		$config = $call->getConfig();

		$arFields = array(
			"ACCOUNT_ID" =>			$params["ACCOUNT_ID"],
			"APPLICATION_ID" =>		$params["APPLICATION_ID"],
			"APPLICATION_NAME" =>	isset($params["APPLICATION_NAME"])?$params["APPLICATION_NAME"]: '-',
			"INCOMING" =>			$params["INCOMING"],
			"CALL_START_DATE" =>	$call->getDateCreate(),
			"CALL_DURATION" =>		isset($params["CALL_DURATION"])? $params["CALL_DURATION"]: $params["DURATION"],
			"CALL_RECORD_URL" => 	$params["URL"],
			"CALL_STATUS" =>		$params["CALL_STATUS"],
			"CALL_FAILED_CODE" =>	$params["CALL_FAILED_CODE"],
			"CALL_FAILED_REASON" =>	$params["CALL_FAILED_REASON"],
			"COST" =>				$params["COST_FINAL"],
			"COST_CURRENCY" =>		$params["COST_CURRENCY"],
			"CALL_VOTE" =>			intval($params["CALL_VOTE"]),
			"CALL_ID" =>			$params["CALL_ID"],
			"CALL_CATEGORY" =>		$params["CALL_CATEGORY"],
			"SESSION_ID" =>			$call->getSessionId(),
			"TRANSCRIPT_PENDING" => $params['TRANSCRIPT_PENDING'] === 'Y' ? 'Y' : 'N',
		);

		if ($params["PHONE_NUMBER"] <> '')
			$arFields["PHONE_NUMBER"] = $params["PHONE_NUMBER"];

		if ($params["CALL_DIRECTION"] <> '')
			$arFields["CALL_DIRECTION"] = $params["CALL_DIRECTION"];

		if ($call->getExternalLineId() && $externalLine = VI\Model\ExternalLineTable::getRowById($call->getExternalLineId()))
		{
			$arFields["PORTAL_NUMBER"] = $externalLine["NORMALIZED_NUMBER"];
		}
		else if ($params["PORTAL_NUMBER"] <> '')
		{
			$arFields["PORTAL_NUMBER"] = $params["PORTAL_NUMBER"];
		}
		else if ($params["ACCOUNT_SEARCH_ID"] <> '')
		{
			$arFields["PORTAL_NUMBER"] = $params["ACCOUNT_SEARCH_ID"];
		}

		if($arFields['CALL_VOTE'] < 1 || $arFields['CALL_VOTE'] > 5)
			$arFields['CALL_VOTE'] = null;

		if ($params["CALL_LOG"] <> '')
			$arFields["CALL_LOG"] = $params["CALL_LOG"];

		if ($arFields["INCOMING"] == CVoxImplantMain::CALL_INFO)
		{
			// infocalls have no responsible
			$arFields["PORTAL_USER_ID"] = null;
		}
		else if ($arFields["CALL_FAILED_CODE"] == 304 && (int)$params["PORTAL_USER_ID"] > 0)
		{
			$arFields["PORTAL_USER_ID"] = (int)$params["PORTAL_USER_ID"];
		}
		else if (
			$arFields["CALL_FAILED_CODE"] == 304
			&& (in_array($call->getIncoming(), [CVoxImplantMain::CALL_INCOMING, CVoxImplantMain::CALL_INCOMING_REDIRECT, CVoxImplantMain::CALL_CALLBACK]))
			&& $call->getPrimaryEntityType() != ''
			&& $call->getPrimaryEntityId() > 0
			&& ($crmResponsibleId = CVoxImplantCrmHelper::getResponsible($call->getPrimaryEntityType(), $call->getPrimaryEntityId()))
		)
		{
			// missed call should be assigned to a responsible user, if a client is found in CRM
			$arFields["PORTAL_USER_ID"] = $crmResponsibleId;
		}
		else if($call->getUserId() > 0)
		{
			$arFields["PORTAL_USER_ID"] = $call->getUserId();
		}
		else
		{
			$arFields["PORTAL_USER_ID"] = intval(self::detectResponsible($call));
		}

		$registerInCrmByBlacklist = (
			$arFields["INCOMING"] != CVoxImplantMain::CALL_INCOMING
			|| $arFields["CALL_FAILED_CODE"] != 423
			|| Bitrix\Main\Config\Option::get("voximplant", "blacklist_register_in_crm", "N") == "Y"
		);

		if(CVoxImplantCrmHelper::shouldCreateLead($call) && $registerInCrmByBlacklist)
		{
			// Create lead if the call was finished too early and a lead was not created (but should have been created)
			if(!$call->getUserId() && $arFields["PORTAL_USER_ID"])
			{
				$call->updateUserId($arFields["PORTAL_USER_ID"]);
			}

			if($call->getUserId())
			{
				CVoxImplantCrmHelper::registerCallInCrm($call);

				if(\CVoxImplantConfig::GetLeadWorkflowExecution() == \CVoxImplantConfig::WORKFLOW_START_IMMEDIATE)
				{
					\CVoxImplantCrmHelper::StartCallTrigger($call);
				}
			}
		}

		if($call->getPrimaryEntityType() != '' && $call->getPrimaryEntityId() > 0)
		{
			$arFields['CRM_ENTITY_TYPE'] = $call->getPrimaryEntityType();
			$arFields['CRM_ENTITY_ID'] = $call->getPrimaryEntityId();
		}

		if(\CVoxImplantConfig::GetLeadWorkflowExecution() == \CVoxImplantConfig::WORKFLOW_START_DEFERRED)
		{
			\CVoxImplantCrmHelper::StartCallTrigger($call);
		}

		if($arFields["CALL_FAILED_CODE"] == 304 && ($call->getIncoming() == \CVoxImplantMain::CALL_INCOMING || $call->getIncoming() == \CVoxImplantMain::CALL_INCOMING_REDIRECT))
		{
			\CVoxImplantCrmHelper::StartMissedCallTrigger($call);
		}

		$arFields['COMMENT'] = $call->getComment() ?: null;

		$insertResult = Bitrix\VoxImplant\StatisticTable::add($arFields);
		if (!$insertResult->isSuccess())
		{
			static::releaseLock($callId);
			return false;
		}
		if($arFields['COST'] > 0)
		{
			static::setLastPaidCallTimestamp(time());
		}

		$arFields['ID'] = $insertResult->getId();

		//recording a missed call
		if (
			$arFields["CALL_FAILED_CODE"] == 304
			&& (
				$call->getIncoming() == \CVoxImplantMain::CALL_INCOMING
				|| $call->getIncoming() == \CVoxImplantMain::CALL_INCOMING_REDIRECT
			)
		)
		{
			$missedCall = [
				'ID' => $arFields['ID'],
				'CALL_START_DATE' => $arFields['CALL_START_DATE'],
				'PHONE_NUMBER' => $arFields['PHONE_NUMBER'],
				'PORTAL_USER_ID' => $arFields['PORTAL_USER_ID']
			];

			$insertMissedCallResult = VI\Model\StatisticMissedTable::add($missedCall);
			if (!$insertMissedCallResult->isSuccess())
			{
				static::releaseLock($callId);
				return false;
			}
		} //if our call answering any missed calls
		elseif (
			$arFields["CALL_FAILED_CODE"] == 200
			&& $call->getIncoming() == \CVoxImplantMain::CALL_OUTGOING
		)
		{
			$missedCalls = VI\Model\StatisticMissedTable::getList([
				'select' => ['ID'],
				'filter' => [
					'=PHONE_NUMBER' => $arFields['PHONE_NUMBER'],
					'=CALLBACK_ID' => null
				],
			])->fetchAll();

			if ($missedCalls)
			{
				foreach ($missedCalls as $missedCall)
				{
					VI\Model\StatisticMissedTable::update($missedCall['ID'], [
							'CALLBACK_ID' => $arFields['ID'],
							'CALLBACK_CALL_START_DATE' => $arFields['CALL_START_DATE']
						]
					);
				}
			}
		}

		if (!$call->isInternalCall() && $call->isCrmEnabled() && $registerInCrmByBlacklist)
		{
			if($call->getCrmActivityId() > 0 && CVoxImplantCrmHelper::shouldAttachCallToActivity($arFields, $call->getCrmActivityId()))
			{
				CVoxImplantCrmHelper::attachCallToActivity($arFields, $call->getCrmActivityId());
				$arFields['CRM_ACTIVITY_ID'] = $call->getCrmActivityId();
			}
			else
			{
				$arFields['CRM_ACTIVITY_ID'] = CVoxImplantCrmHelper::AddCall($arFields, array(
					'WORKTIME_SKIPPED' => $call->isWorktimeSkipped() ? 'Y' : 'N',
					'CRM_BINDINGS' => $call->getCrmBindings()
				));

				if($call->getCrmActivityId() && CVoxImplantCrmHelper::shouldCompleteActivity($arFields))
				{
					CVoxImplantCrmHelper::completeActivity($call->getCrmActivityId());
				}
			}

			VI\StatisticTable::update($arFields['ID'], array(
				'CRM_ACTIVITY_ID' => $arFields['CRM_ACTIVITY_ID']
			));

			if($call->getPrimaryEntityType() != '' && $call->getPrimaryEntityId() > 0)
			{
				$viMain = new CVoxImplantMain($arFields["PORTAL_USER_ID"]);
				$dialogData = $viMain->GetDialogInfo($arFields['PHONE_NUMBER'], '', false);
				if(!$dialogData['UNIFIED'])
				{
					CVoxImplantMain::UpdateChatInfo(
						$dialogData['DIALOG_ID'],
						array(
							'CRM' => $call->isCrmEnabled() ? 'Y' : 'N',
							'CRM_ENTITY_TYPE' => $call->getPrimaryEntityType(),
							'CRM_ENTITY_ID' => $call->getPrimaryEntityId(),
							'PHONE_NUMBER' => $arFields['PHONE_NUMBER']
						)
					);
				}
			}
		}

		$chatMessage = self::GetMessageForChat($arFields, $params['URL'] != '');
		if($chatMessage != '')
		{
			$attach = null;

			if(CVoxImplantConfig::GetChatAction() == CVoxImplantConfig::INTERFACE_CHAT_APPEND)
			{
				$attach = static::GetAttachForChat($arFields, $params['URL'] != '');
			}

			if($attach)
				self::SendMessageToChat($arFields["PORTAL_USER_ID"], $arFields["PHONE_NUMBER"], $arFields["INCOMING"], null, $attach);
			else
				self::SendMessageToChat($arFields["PORTAL_USER_ID"], $arFields["PHONE_NUMBER"], $arFields["INCOMING"], $chatMessage);
		}

		if ($params['URL'] != '')
		{
			$attachToCrm = $call->isCrmEnabled();
			$recordUrl = \Bitrix\Main\Web\Uri::urnEncode($params['URL']);
			self::DownloadAgent($insertResult->getId(), $recordUrl, $attachToCrm);
		}

		if ($params["ACCOUNT_PAYED"] <> '' && in_array($params["ACCOUNT_PAYED"], Array('Y', 'N')))
		{
			CVoxImplantAccount::SetPayedFlag($params["ACCOUNT_PAYED"]);
		}

		if(CVoxImplantConfig::GetLeadWorkflowExecution() == CVoxImplantConfig::WORKFLOW_START_DEFERRED)
		{
			$createdCrmEntities = $call->getCreatedCrmEntities();

			foreach ($createdCrmEntities as $entity)
			{
				if($entity['ENTITY_TYPE'] === 'LEAD')
				{
					CVoxImplantCrmHelper::StartLeadWorkflow($entity['ENTITY_ID']);
				}
			}
		}

		if($call->getCrmCallList() > 0)
		{
			try
			{
				CVoxImplantCrmHelper::attachCallToCallList($call->getCrmCallList(), $arFields);
			}
			catch (\Exception $exception)
			{
				Application::getInstance()->getExceptionHandler()->writeToLog($exception);
			}
		}

		/* repeat missed callback, if neeeded */
		if($call->getIncoming() == CVoxImplantMain::CALL_CALLBACK && $params["CALL_FAILED_CODE"] == '304')
		{
			if(self::shouldRepeatCallback($call->toArray(), $config))
			{
				self::repeatCallback($call->toArray(), $config);
			}
		}

		static::sendCallEndEvent($arFields);
		if($arFields['INCOMING'] == CVoxImplantMain::CALL_INFO)
		{
			$callEvent = new Event(
				'voximplant',
				'OnInfoCallResult',
				array(
					$arFields['CALL_ID'],
					array(
						'RESULT' => ($arFields['CALL_FAILED_CODE'] == '200'),
						'CODE' => $arFields['CALL_FAILED_CODE'],
						'REASON' => $arFields['CALL_FAILED_REASON']
					)
				)
			);
			EventManager::getInstance()->send($callEvent);
		}

		VI\Call::delete($callId);
		static::releaseLock($callId);
		return true;
	}

	public static function DownloadAgent(int $historyID, string $recordUrl, $attachToCrm = true, $retryOnFailure = true): bool
	{
		$recordUrl = \Bitrix\Main\Web\Uri::urnDecode($recordUrl);
		self::WriteToLog('Downloading record ' . $recordUrl);
		$attachToCrm = ($attachToCrm === true);

		if ($recordUrl == '' || $historyID <= 0)
		{
			return false;
		}

		$urlPath = parse_url($recordUrl, PHP_URL_PATH);

		if ($urlPath)
		{
			$tempPath = \CFile::GetTempName('', bx_basename($urlPath));
		}
		else
		{
			$tempPath = \CFile::GetTempName('', bx_basename($recordUrl));
		}

		$http = VI\HttpClientFactory::create(array(
			"disableSslVerification" => true
		));

		try
		{
			$http->download($recordUrl, $tempPath);

			if ($http->getStatus() !== 200)
			{
				self::DownloadAgentRequestErrorHandler($historyID, $recordUrl, $attachToCrm, $retryOnFailure, $http);

				return false;
			}

			static::WriteToLog("Call record downloaded successfully. Url: " . $recordUrl);

			$fileType = $http->getHeaders()->getContentType() ?: CFile::GetContentType($tempPath);
			$recordFile = CFile::MakeFileArray($tempPath, $fileType);

			if (is_array($recordFile) && $recordFile['size'] && $recordFile['size'] > 0)
			{
				if(mb_strpos($recordFile['name'], '.') === false)
				{
					$recordFile['name'] .= '.mp3';
				}

				$history = VI\StatisticTable::getById($historyID);
				$arHistory = $history->fetch();

				static::AttachRecord($arHistory['CALL_ID'], $recordFile);
			}
		}
		catch (Exception $ex)
		{
			self::WriteToLog('Error caught during downloading record: ' . PHP_EOL . print_r($ex, true));
		}

		return false;
	}

	private static function DownloadAgentRequestErrorHandler(
		int $historyID,
		string $recordUrl,
		bool $attachToCrm,
		bool $retryOnFailure,
		\Bitrix\Main\Web\HttpClient $httpClient
	)
	{
		if($retryOnFailure)
		{
			$recordUrl = \Bitrix\Main\Web\Uri::urnEncode($recordUrl);
			CAgent::AddAgent(
				"CVoxImplantHistory::DownloadAgent('{$historyID}','" . EscapePHPString($recordUrl, "'") . "','{$attachToCrm}', false);",
				'voximplant',
				'N',
				60,
				'',
				'Y',
				ConvertTimeStamp(time() + CTimeZone::GetOffset() + 60, 'FULL')
			);
		}

		$errors = [];
		foreach($httpClient->getError() as $code => $message)
		{
			$errors[] = $code . ": " . $message;
		}
		$error = !empty($errors) ? implode("; " , $errors) : $httpClient->getStatus();

		static::WriteToLog("Call record download error. Url: " . $recordUrl . "; Error: " . $error);
	}

	public static function AttachRecord($callId, array $recordFileFields)
	{
		$result = new Result();
		$arHistory = VI\StatisticTable::getRow([
			'select' => ['*'],
			'filter' => ['=CALL_ID' => $callId]
		]);

		if(!$arHistory)
		{
			return $result->addError(new \Bitrix\Main\Error("Call is not found", "NOT_FOUND"));
		}

		$historyID = $arHistory["ID"];
		$recordFileFields['MODULE_ID'] = 'voximplant';
		$fileId = CFile::SaveFile($recordFileFields, 'voximplant', true);

		if(!$fileId)
		{
			return $result->addError(new \Bitrix\Main\Error("Could not save file", "SAVE_FILE_ERROR"));
		}

		VI\StatisticTable::update($historyID, ['CALL_RECORD_ID' => $fileId]);
		$elementId = CVoxImplantDiskHelper::SaveFile($arHistory, CFile::GetFileArray($fileId));
		$elementId = (int)$elementId;
		VI\StatisticTable::update($historyID, ['CALL_WEBDAV_ID' => $elementId]);

		if (VI\Limits::getRecordLimit() > 0)
		{
			VI\Limits::registerRecord();
		}

		CVoxImplantCrmHelper::AttachRecordToCall([
			'CALL_ID' => $callId,
			'CALL_RECORD_ID' => $fileId,
			'CALL_WEBDAV_ID' => $elementId,
		]);

		return $result;
	}

	public static function GetForPopup($id)
	{
		$id = intval($id);
		if ($id <= 0)
			return false;

		$history = VI\StatisticTable::getById($id);
		$params = $history->fetch();
		if (!$params)
			return false;

		$params = self::PrepereData($params);

		$arResult = Array(
			'PORTAL_USER_ID' => $params['PORTAL_USER_ID'],
			'PHONE_NUMBER' => $params['PHONE_NUMBER'],
			'PHONE_NUMBER_FORMATTED' => \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($params['PHONE_NUMBER'])->format(),
			'INCOMING_TEXT' => $params['INCOMING_TEXT'],
			'CALL_ICON' => $params['CALL_ICON'],
			'CALL_FAILED_CODE' => $params['CALL_FAILED_CODE'],
			'CALL_FAILED_REASON' => $params['CALL_FAILED_REASON'],
			'CALL_DURATION_TEXT' => $params['CALL_DURATION_TEXT'],
			'COST_TEXT' => $params['COST_TEXT'],
			'CALL_RECORD_HREF' => $params['CALL_RECORD_HREF'],
		);

		return $arResult;
	}

	public static function PrepereData($params)
	{
		if ($params["INCOMING"] == "N")
		{
			$params["INCOMING"] = CVoxImplantMain::CALL_OUTGOING;
		}
		else if ($params["INCOMING"] == "N")
		{
			$params["INCOMING"] = CVoxImplantMain::CALL_INCOMING;
		}
		if ($params["PHONE_NUMBER"] == "hidden")
		{
			$params["PHONE_NUMBER"] = GetMessage("IM_PHONE_NUMBER_HIDDEN");
		}

		$params["CALL_FAILED_REASON"] = static::getStatusText($params["CALL_FAILED_CODE"]);
		$params["INCOMING_TEXT"] = static::getDirectionText($params["INCOMING"]);

		if ($params["INCOMING"] == CVoxImplantMain::CALL_OUTGOING)
		{
			if ($params["CALL_FAILED_CODE"] == 200)
				$params["CALL_ICON"] = 'outgoing';
		}
		else if ($params["INCOMING"] == CVoxImplantMain::CALL_INCOMING)
		{
			if ($params["CALL_FAILED_CODE"] == 200)
				$params["CALL_ICON"] = 'incoming';
		}
		else if ($params["INCOMING"] == CVoxImplantMain::CALL_INCOMING_REDIRECT)
		{
			if ($params["CALL_FAILED_CODE"] == 200)
				$params["CALL_ICON"] = 'incoming-redirect';
		}
		else if($params["INCOMING"] == CVoxImplantMain::CALL_CALLBACK)
		{
			if ($params["CALL_FAILED_CODE"] == 200)
				$params["CALL_ICON"] = 'incoming'; //todo: icon?
		}
		else if($params["INCOMING"] == CVoxImplantMain::CALL_INFO)
		{
			if ($params["CALL_FAILED_CODE"] == 200)
				$params["CALL_ICON"] = 'outgoing';
		}

		if ($params["CALL_FAILED_CODE"] == 304)
		{
			$params["CALL_ICON"] = 'skipped';
		}
		else if ($params["CALL_FAILED_CODE"] != 200)
		{
			$params["CALL_ICON"] = 'decline';
		}

		$params["CALL_DURATION_TEXT"] = static::convertDurationToText($params['CALL_DURATION']);

		if (CModule::IncludeModule("catalog"))
		{
			$params["COST_TEXT"] = FormatCurrency($params["COST"], ($params["COST_CURRENCY"] == "RUR" ? "RUB" : $params["COST_CURRENCY"]));
			if(isset($params['TRANSCRIPT_COST']) && $params['TRANSCRIPT_COST'] > 0)
			{
				$params["TRANSCRIPT_COST_TEXT"] =  FormatCurrency($params["TRANSCRIPT_COST"], ($params["COST_CURRENCY"] == "RUR" ? "RUB" : $params["COST_CURRENCY"]));
			}
		}
		else
		{
			$params["COST_TEXT"] = $params["COST"]." ".GetMessage("VI_CURRENCY_".$params["COST_CURRENCY"]);
			if(isset($params['TRANSCRIPT_COST']) && $params['TRANSCRIPT_COST'] > 0)
			{
				$params["TRANSCRIPT_COST_TEXT"] =  $params["TRANSCRIPT_COST"]." ".GetMessage("VI_CURRENCY_".$params["COST_CURRENCY"]);
			}
		}

		if (!$params["COST_TEXT"])
		{
			$params["COST_TEXT"] = '-';
		}

		if (intval($params["CALL_RECORD_ID"]) > 0)
		{
			$recordFile = CFile::GetFileArray($params["CALL_RECORD_ID"]);
			if ($recordFile !== false)
			{
				$params["CALL_RECORD_HREF"] = $recordFile['SRC'];
			}
		}

		$params["CALL_WEBDAV_ID"] = (int)$params["CALL_WEBDAV_ID"];
		if($params["CALL_WEBDAV_ID"] > 0 && \Bitrix\Main\Loader::includeModule('disk'))
		{
			$fileId = $params["CALL_WEBDAV_ID"];
			$file = \Bitrix\Disk\File::loadById($fileId);
			if(!is_null($file))
				$params['CALL_RECORD_DOWNLOAD_URL'] = \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlForDownloadFile($file, true);
		}

		return $params;
	}

	public static function TransferMessage($userId, $transferUserId, $phoneNumber, $transferPhone = '')
	{
		$userName = '';
		$arSelect = Array("ID", "LAST_NAME", "NAME", "LOGIN", "SECOND_NAME", "PERSONAL_GENDER");
		$dbUsers = CUser::GetList('', '', array('ID' => $transferUserId), array('FIELDS' => $arSelect));
		if ($arUser = $dbUsers->Fetch())
			$userName = CUser::FormatName(CSite::GetNameFormat(false), $arUser, true, false);

		self::SendMessageToChat(
			$userId,
			$phoneNumber,
			CVoxImplantMain::CALL_INCOMING_REDIRECT,
			GetMessage('VI_CALL_TRANSFER', Array('#USER#' => $userName)).($transferPhone != '' ? ' ('.$transferPhone.')' : '')
		);

		return true;
	}

	public static function TransferMessagePSTN($userId, $phoneNumber, $transferPhone)
	{
		self::SendMessageToChat(
			$userId,
			$phoneNumber,
			CVoxImplantMain::CALL_INCOMING_REDIRECT,
			GetMessage('VI_CALL_TRANSFER_PSTN', ['#NUMBER#' => $transferPhone])
		);

		return true;
	}

	public static function SendMessageToChat($userId, $phoneNumber, $incomingType, $message, $attach = null)
	{
		$ViMain = new CVoxImplantMain($userId);
		$dialogInfo = $ViMain->GetDialogInfo($phoneNumber, "", false);
		$ViMain->SendChatMessage($dialogInfo['DIALOG_ID'], $incomingType, $message, $attach);

		return true;
	}

	/**
	 * Creates message for the chat associated with phone number.
	 * @param array $callFields
	 * @param bool $hasRecord
	 * @return string
	 */
	public static function GetMessageForChat($callFields, $hasRecord = false, $prependPlus = true)
	{
		$result = '';
		if ($callFields["PHONE_NUMBER"] <> '' && $callFields["PORTAL_USER_ID"] > 0 && $callFields["CALL_FAILED_CODE"] != 423)
		{
			$formattedNumber = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($callFields["PHONE_NUMBER"])->format();
			$formattedNumber = "[CALL={$formattedNumber}]" . $formattedNumber . "[/CALL]";

			if ($callFields["INCOMING"] == CVoxImplantMain::CALL_OUTGOING)
			{
				if ($callFields['CALL_FAILED_CODE'] == '603-S')
				{
					$result = GetMessage('VI_OUT_CALL_DECLINE_SELF', Array('#NUMBER#' => $formattedNumber));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 603)
				{
					$result = GetMessage('VI_OUT_CALL_DECLINE', Array('#NUMBER#' => $formattedNumber));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 486)
				{
					$result = GetMessage('VI_OUT_CALL_BUSY', Array('#NUMBER#' => $formattedNumber));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 480)
				{
					$result = GetMessage('VI_OUT_CALL_UNAVAILABLE', Array('#NUMBER#' => $formattedNumber));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 404 || $callFields['CALL_FAILED_CODE'] == 484)
				{
					$result = GetMessage('VI_OUT_CALL_ERROR_NUMBER', Array('#NUMBER#' => $formattedNumber));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 402)
				{
					$result = GetMessage('VI_OUT_CALL_NO_MONEY', Array('#NUMBER#' => $formattedNumber));
				}
				else
				{
					$result = GetMessage('VI_OUT_CALL_END', Array(
						'#NUMBER#' => $formattedNumber,
						'#INFO#' => '[PCH='.$callFields['ID'].']'.GetMessage('VI_CALL_INFO').'[/PCH]',
					));
				}
			}
			else if ($callFields['INCOMING'] == CVoxImplantMain::CALL_CALLBACK)
			{
				if ($callFields['CALL_FAILED_CODE'] == '603-S')
				{
					$result = GetMessage('VI_CALLBACK_DECLINE_SELF', Array('#NUMBER#' => $formattedNumber));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 603)
				{
					$result = GetMessage('VI_CALLBACK_DECLINE', Array('#NUMBER#' => $formattedNumber));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 486)
				{
					$result = GetMessage('VI_CALLBACK_BUSY', Array('#NUMBER#' => $formattedNumber));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 480)
				{
					$result = GetMessage('VI_CALLBACK_UNAVAILABLE', Array('#NUMBER#' => $formattedNumber));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 404 || $callFields['CALL_FAILED_CODE'] == 484)
				{
					$result = GetMessage('VVI_CALLBACK_ERROR_NUMBER', Array('#NUMBER#' => $formattedNumber));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 402)
				{
					$result = GetMessage('VI_CALLBACK_NO_MONEY', Array('#NUMBER#' => $formattedNumber));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 304)
				{
					$subMessage = '[PCH='.$callFields['ID'].']'.GetMessage('VI_CALL_INFO').'[/PCH]';
					$result = GetMessage('VI_CALLBACK_SKIP', Array('#NUMBER#' => $formattedNumber, '#INFO#' => $subMessage));
				}
				else
				{
					$result = GetMessage('VI_CALLBACK_END', Array(
						'#NUMBER#' => $formattedNumber,
						'#INFO#' => '[PCH='.$callFields['ID'].']'.GetMessage('VI_CALL_INFO').'[/PCH]',
					));
				}
			}
			else if($callFields['INCOMING'] == CVoxImplantMain::CALL_INCOMING || $callFields['INCOMING'] == CVoxImplantMain::CALL_INCOMING_REDIRECT)
			{
				if ($callFields['CALL_FAILED_CODE'] == 304)
				{
					if ($hasRecord)
						$subMessage = GetMessage('VI_CALL_VOICEMAIL', Array('#LINK_START#' => '[PCH='.$callFields['ID'].']', '#LINK_END#' => '[/PCH]',));
					else
						$subMessage = '[PCH='.$callFields['ID'].']'.GetMessage('VI_CALL_INFO').'[/PCH]';

					$result = GetMessage('VI_IN_CALL_SKIP', Array(
						'#NUMBER#' => $formattedNumber,
						'#INFO#' => $subMessage,
					));
				}
				else
				{
					$result = GetMessage('VI_IN_CALL_END', Array(
						'#NUMBER#' => $formattedNumber,
						'#INFO#' => '[PCH='.$callFields['ID'].']'.GetMessage('VI_CALL_INFO').'[/PCH]',
					));
				}
			}
		}
		return $result;
	}

	public static function GetAttachForChat($callFields, $hasRecord = false, $prependPlus = true)
	{
		if(!CModule::IncludeModule('im'))
			return null;

		$entityData = \CVoxImplantCrmHelper::getEntityFields($callFields['CRM_ENTITY_TYPE'], $callFields['CRM_ENTITY_ID']);
		if(!$entityData)
			return null;

		$result = new \CIMMessageParamAttach(null, '#dfe2e5');
		$result->AddMessage(static::GetMessageForChat($callFields, $hasRecord, $prependPlus));
		$result->AddLink(array(
			"NAME" => $entityData["DESCRIPTION"].": ".$entityData["NAME"],
			"LINK" => $entityData["SHOW_URL"]
		));
		return $result;
	}

	public static function GetCallTypes()
	{
		return array(
			CVoxImplantMain::CALL_OUTGOING => GetMessage("VI_OUTGOING"),
			CVoxImplantMain::CALL_INCOMING => GetMessage("VI_INCOMING"),
			CVoxImplantMain::CALL_INCOMING_REDIRECT => GetMessage("VI_INCOMING_REDIRECT"),
			CVoxImplantMain::CALL_CALLBACK => GetMessage("VI_CALLBACK"),
			CVoxImplantMain::CALL_INFO => GetMessage("VI_INFOCALL"),
		);
	}


	/**
	 * Returns brief call details for CRM or false if call is not found.
	 * @param string $callId Id of the call.
	 * @return array(STATUS_CODE, STATUS_TEXT, SUCCESSFUL) | false
	 */
	public static function getBriefDetails($callId)
	{
		$call = VI\StatisticTable::getRow(['filter' => ['=CALL_ID' => $callId]]);
		if (!$call)
		{
			return false;
		}

		return [
			'CALL_ID' => $call['CALL_ID'],
			'CALL_TYPE' => $call['INCOMING'],
			'CALL_TYPE_TEXT' => static::getDirectionText($call['INCOMING'], true),
			'PORTAL_NUMBER' => $call['PORTAL_NUMBER'],
			'PORTAL_LINE' => \CVoxImplantConfig::GetLine($call['PORTAL_NUMBER']),
			'STATUS_CODE '=> $call['CALL_FAILED_CODE'],
			'STATUS_TEXT' => self::getStatusText($call["CALL_FAILED_CODE"]),
			'SUCCESSFUL' => $call['CALL_FAILED_CODE'] == '200',
			'DURATION' => (int)$call['CALL_DURATION'],
			'HAS_TRANSCRIPT' => ($call['TRANSCRIPT_ID'] > 0),
			'TRANSCRIPT_PENDING' => ($call['TRANSCRIPT_PENDING'] == 'Y'),
			'DURATION_TEXT' => static::convertDurationToText($call['CALL_DURATION'], CVoxImplantHistory::DURATION_FORMAT_BRIEF),
			'COMMENT' =>  $call['COMMENT'],
			'CALL_VOTE' => $call['CALL_VOTE'],
		];
	}

	public static function getStatusText($statusCode)
	{
		return in_array($statusCode, array("200","304","603-S","603","403","404","486","484","500", "503","480","402","423", "402-B24")) ? GetMessage("VI_STATUS_".$statusCode) : GetMessage("VI_STATUS_OTHER");
	}

	/**
	 * Returns text description for a call direction.
	 * @param int $direction Code of the direction.
	 * @return mixed|string
	 */
	public static function getDirectionText($direction, $full = false)
	{
		$phrase = '';
		if ($direction == CVoxImplantMain::CALL_OUTGOING)
			$phrase = "VI_OUTGOING";
		else if ($direction == CVoxImplantMain::CALL_INCOMING)
			$phrase = "VI_INCOMING";
		else if ($direction == CVoxImplantMain::CALL_INCOMING_REDIRECT)
			$phrase = "VI_INCOMING_REDIRECT";
		else if($direction == CVoxImplantMain::CALL_CALLBACK)
			$phrase = "VI_CALLBACK";
		else if($direction == CVoxImplantMain::CALL_INFO)
			$phrase = "VI_INFOCALL";

		if($phrase != '' && $full)
			$phrase = $phrase . '_FULL';


		return ($phrase == '') ? '' : GetMessage($phrase);
	}

	public static function saveComment($callId, $comment)
	{
		$call = VI\StatisticTable::getRow(array('filter' => array('=CALL_ID' => $callId)));
		if($call)
		{
			VI\StatisticTable::update($call['ID'], array(
				'COMMENT' => $comment
			));
		}
	}

	public static function WriteToLog($data, $title = '')
	{
		if (!COption::GetOptionInt("voximplant", "debug"))
			return false;

		if (is_array($data))
		{
			unset($data['HASH']);
			unset($data['BX_HASH']);
		}
		else if (is_object($data))
		{
			if ($data->HASH)
			{
				$data->HASH = '';
			}
			if ($data->BX_HASH)
			{
				$data->BX_HASH = '';
			}
		}
		$f=fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant.log", "a+t");
		$w=fwrite($f, "\n------------------------\n".date("Y.m.d G:i:s")."\n".($title <> ''? $title: 'DEBUG')."\n".print_r($data, 1)."\n------------------------\n");
		fclose($f);

		return true;
	}

	/**
	 * @param int $duration Duration in seconds.
	 * @return string Text form of duration.
	 */
	public static function convertDurationToText($duration, $format = self::DURATION_FORMAT_FULL)
	{
		$duration = (int)$duration;
		$minutes = floor($duration / 60);
		$seconds = $duration % 60;

		if($format == self::DURATION_FORMAT_FULL)
			return ($minutes > 0 ? $minutes." ".GetMessage("VI_MIN") : "") . ($minutes > 0 && $seconds > 0 ? ", " : "") . ($seconds > 0 ? $seconds . " " . GetMessage("VI_SEC") : '');
		else
			return sprintf("%02d:%02d", $minutes, $seconds);
	}

	/**
	 * This function guesses responsible person to assign missed call.
	 * @param VI\Call $call Call fields, as selected from the Bitrix\Voximplant\CallTable.
	 * @return int|false Id of the responsible, or false if responsible is not found.
	 */
	public static function detectResponsible(VI\Call $call)
	{
		CVoxImplantHistory::WriteToLog($call->toArray(), "detectResponsible");
		$config = $call->getConfig();
		if($call->getQueueId() > 0)
		{
			$queue = VI\Queue::createWithId($call->getQueueId());
			if($queue instanceof VI\Queue)
			{
				$queueUser = $queue->getFirstUserId();
				if ($queueUser > 0)
				{
					$queue->touchUser($queueUser);
					return $queueUser;
				}
			}
		}

		if(is_array($config) && $config['CRM'] == 'Y' && $config['CRM_FORWARD'] == 'Y')
		{
			if($call->getPrimaryEntityType() != '' && $call->getPrimaryEntityId() > 0)
			{
				$responsibleId = CVoxImplantCrmHelper::getResponsible($call->getPrimaryEntityType(), $call->getPrimaryEntityId());
				if($responsibleId > 0)
				{
					return $responsibleId;
				}
			}
			else
			{
				$responsibleInfo = CVoxImplantIncoming::getCrmResponsible($call, false);
				if($responsibleInfo)
				{
					return $responsibleInfo['USER_ID'];
				}
			}
		}

		if(is_array($config) && $config['QUEUE_ID'] > 0)
		{
			$queue = VI\Queue::createWithId($config['QUEUE_ID']);
			if($queue instanceof VI\Queue)
			{
				$queueUser = $queue->getFirstUserId();
				if ($queueUser > 0)
				{
					$queue->touchUser($queueUser);
					return $queueUser;
				}
			}
		}

		return false;
	}

	/**
	 * This function returns true if callback should be repeated, according to the line config.
	 * @param array $call Call fields, as selected from the Bitrix\Voximplant\CallTable.
	 * @param array $config Line config, as selected from the Bitrix\Voximplant\ConfigTable
	 * @return true.
	 */
	public static function shouldRepeatCallback($call, $config)
	{
		if(!is_array($call) || !is_array($config))
			return false;

		if($config['CALLBACK_REDIAL'] != 'Y')
			return false;

		if($config['CALLBACK_REDIAL_ATTEMPTS'] <= 0)
			return false;

		if(!isset($call['CALLBACK_PARAMETERS']['redialAttempt']))
			return false;

		$currentAttempt = $call['CALLBACK_PARAMETERS']['redialAttempt'];

		return ($currentAttempt < $config['CALLBACK_REDIAL_ATTEMPTS']);
	}

	/**
	 * Enqueues callback for repeating, according to the line config.
	 * @param array $call Call fields, as selected from the Bitrix\Voximplant\CallTable.
	 * @param array $config Line config, as selected from the Bitrix\Voximplant\ConfigTable
	 * @return bool|mixed|object
	 */
	public static function repeatCallback($call, $config)
	{
		$apiClient = new CVoxImplantHttp();

		if($config['CALLBACK_REDIAL_PERIOD'] <= 0)
			return false;

		$callbackParameters = $call['CALLBACK_PARAMETERS'];
		$callbackParameters['redialAttempt']++;

		return $apiClient->enqueueCallback($call['CALLBACK_PARAMETERS'], time() + $config['CALLBACK_REDIAL_PERIOD']);
	}

	/**
	 * Recreates lost call using AddCallHistory request fields
	 * @param $params
	 */
	public static function recreateCall($params)
	{
		$config = CVoxImplantConfig::GetConfigBySearchId($params['ACCOUNT_SEARCH_ID']);

		$call = VI\Call::create([
			'CALL_ID' => $params['CALL_ID'],
			'CONFIG_ID' => $config['ID'],
			'DATE_CREATE' => \Bitrix\Main\Type\DateTime::createFromTimestamp($params['CALL_START_TS']),
			'INCOMING' => $params['INCOMING'],
			'CALLER_ID' => $params['PHONE_NUMBER'],
			'USER_ID' => $params['PORTAL_USER_ID'],
			'SESSION_ID' => $params['SESSION_ID']
		]);

		$crmData = CVoxImplantCrmHelper::getCrmEntities($call);
		$call->updateCrmEntities($crmData);
		$activityBindings = CVoxImplantCrmHelper::getActivityBindings($call);
		if(is_array($activityBindings))
		{
			$call->updateCrmBindings($activityBindings);
		}

		return $call;
	}

	/**
	 * @param array $statisticFields Call record, as selected from StatisticTable
	 * @see \Bitrix\Voximplant\StatisticTable
	 */
	public static function sendCallEndEvent(array $statisticFields)
	{
		foreach(GetModuleEvents("voximplant", "onCallEnd", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, Array(Array(
				'CALL_ID' => $statisticFields['CALL_ID'],
				'CALL_TYPE' => $statisticFields['INCOMING'],
				'PHONE_NUMBER' => $statisticFields['PHONE_NUMBER'],
				'PORTAL_NUMBER' => $statisticFields['PORTAL_NUMBER'],
				'PORTAL_USER_ID' => $statisticFields['PORTAL_USER_ID'],
				'CALL_DURATION' => $statisticFields['CALL_DURATION'],
				'CALL_START_DATE' => $statisticFields['CALL_START_DATE'],
				'COST' => $statisticFields['COST'],
				'COST_CURRENCY' => $statisticFields['COST_CURRENCY'],
				'CALL_FAILED_CODE' => $statisticFields['CALL_FAILED_CODE'],
				'CALL_FAILED_REASON' => $statisticFields['CALL_FAILED_REASON'],
				'CRM_ACTIVITY_ID' => $statisticFields['CRM_ACTIVITY_ID'],
			)));
		}
	}

	/**
	 * @param string $callId
	 * @return bool
	 */
	public static function getLock($callId)
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();
		$lockName = $sqlHelper->forSql(self::CALL_LOCK_PREFIX . "_" . $callId);
		$lockRow = Application::getConnection()->query("SELECT GET_LOCK('{$lockName}', 0) as L")->fetch();
		return $lockRow["L"] == "1";
	}

	/**
	 * @param string $callId
	 * @return bool
	 */
	public static function releaseLock($callId)
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();
		$lockName = $sqlHelper->forSql(self::CALL_LOCK_PREFIX . "_" . $callId);
		Application::getConnection()->query("SELECT RELEASE_LOCK('{$lockName}')");
		return true;
	}

	public static function setLastPaidCallTimestamp($ts)
	{
		\Bitrix\Main\Config\Option::set('voximplant', 'last_paid_call_timestamp', $ts);
	}

	public static function getLastPaidCallTimestamp()
	{
		return \Bitrix\Main\Config\Option::get('voximplant', 'last_paid_call_timestamp', 0);
	}
}
