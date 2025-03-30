<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Application;
use Bitrix\Main\Event;
use Bitrix\Main\Web\Json;

if (!\CModule::IncludeModule("voximplant"))
{
	return false;
}

\CVoxImplantHistory::WriteToLog($_POST, 'PORTAL HIT');

$version = (int)\Bitrix\Main\Context::getCurrent()->getRequest()->getHeader("X-Version") ?: 1;
if ($version === 1)
{
	$params = $_POST;
}
elseif ($version === 2)
{
	try
	{
		$params = \Bitrix\Main\Web\Json::decode(\Bitrix\Main\Context::getCurrent()->getRequest()->getInput());
	}
	catch (\Exception $e)
	{
		$params = null;
	}
}

$output = '';

if (is_array($params))
{
	if ($version === 2)
	{
		$hash = \Bitrix\Main\Context::getCurrent()->getRequest()->getHeader("X-Request-Signature");
	}
	elseif (isset($params["BX_HASH"]))
	{
		$hash = $params["BX_HASH"];
		unset($params["BX_HASH"]);
	}
	elseif (isset($params["HASH"]))
	{
		$hash = $params["HASH"];
		unset($params["HASH"]);
	}

	if (isset($params['BX_TYPE']) && $params['BX_TYPE'] === 'B24')
	{
		$expectedSignature = \CVoxImplantHttp::RequestSign($params['BX_TYPE'], md5(implode("|", $params)."|".BX24_HOST_NAME));
	}
	elseif (isset($params['BX_TYPE']) && $params['BX_TYPE'] === 'CP')
	{
		$expectedSignature = \CVoxImplantHttp::RequestSign($params['BX_TYPE'], md5(implode("|", $params)));
	}
	else
	{
		$expectedSignature = \CVoxImplantHttp::CheckDirectRequest();
	}
	$params = array_merge(initParams(), $params);

	if ($expectedSignature === $hash)
	{
		if ($params["BX_COMMAND"] !== "add_history" && !in_array($params["COMMAND"], ["OutgoingRegister", "AddCallHistory"]) && isset($params['PHONE_NUMBER']) && isset($params['ACCOUNT_SEARCH_ID']))
		{
			$params['PHONE_NUMBER'] = $params['ACCOUNT_SEARCH_ID'];
		}

		if (isset($_GET['b24_direct'], $params['PORTAL_USER_ID'], $params['USER_ID']))
		{
			$params['USER_ID'] = $params['PORTAL_USER_ID'];
		}

		if ($params["COMMAND"] === "OutgoingRegister")
		{
			if (isset($params['CALLER_ID'], $params['ACCOUNT_SEARCH_ID']))
			{
				$params['CALLER_ID'] = $params['ACCOUNT_SEARCH_ID'];
			}

			$action = \CVoxImplantOutgoing::Init([
				'ACCOUNT_SEARCH_ID' => $params['ACCOUNT_SEARCH_ID'],
				'CONFIG_ID' => $params['CONFIG_ID'],
				'USER_ID' => $params['USER_ID'],
				'USER_DIRECT_CODE' => $params['USER_DIRECT_CODE'],
				'PHONE_NUMBER' => $params['PHONE_NUMBER'],
				'CALL_ID' => $params['CALL_ID'],
				'CALL_DEVICE' => $params['CALL_DEVICE'],
				'CALLER_ID' => $params['CALLER_ID'],
				'ACCESS_URL' => $params['ACCESS_URL'],
				'CRM' => $params['CRM'],
				'CRM_ENTITY_TYPE' => $params['CRM_ENTITY_TYPE'],
				'CRM_ENTITY_ID' => $params['CRM_ENTITY_ID'],
				'CRM_ACTIVITY_ID' => $params['CRM_ACTIVITY_ID'],
				'CRM_CALL_LIST' => $params['CRM_CALL_LIST'],
				'CRM_BINDINGS' => $params['CRM_BINDINGS'] == '' ? [] : Json::decode($params['CRM_BINDINGS']),
				'SESSION_ID' => $params['SESSION_ID']
			]);

			foreach (GetModuleEvents("voximplant", "onCallInit", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, [
					[
					'CALL_ID' => $params['CALL_ID'],
					'CALL_TYPE' => \CVoxImplantMain::CALL_OUTGOING,
					'ACCOUNT_SEARCH_ID' => $params['ACCOUNT_SEARCH_ID'],
					'PHONE_NUMBER' => $params['PHONE_NUMBER'],
					'CALLER_ID' => $params['CALLER_ID'],
					]
				]);
			}

			\CVoxImplantHistory::WriteToLog($action, 'OUTGOING REGISTER');

			$output = $action->toJson();
		}
		elseif ($params["COMMAND"] === "CancelUserInvite")
		{
			$call = \Bitrix\Voximplant\Call::load($params['CALL_ID']);
			if ($call)
			{
				$call->removeUsers($params['USERS']);
			}
			$output = Json::encode(['result' => 'OK']);
		}
		elseif ($params["COMMAND"] === "CompleteTransfer")
		{
			$result = \Bitrix\Voximplant\Transfer\Transferor::completeTransfer($params['CALL_ID'], $params['USER_ID'], $params['DEVICE']);

			$output = $result->toJson();
		}
		elseif ($params["COMMAND"] === "CancelTransfer")
		{
			\Bitrix\Voximplant\Transfer\Transferor::cancelTransfer($params['CALL_ID'], $params['CODE'], $params['REASON']);

			$output = Json::encode(['result' => 'OK']);
		}
		elseif ($params["COMMAND"] === "StartBlindTransfer")
		{
			$result = \Bitrix\Voximplant\Transfer\Transferor::startBlindTransfer($params['CALL_ID'], $params['USER_ID']);

			$output = Json::encode(['result' => 'OK']);
		}
		elseif ($params["COMMAND"] === "CompletePhoneTransfer")
		{
			\Bitrix\Voximplant\Transfer\Transferor::completePhoneTransfer($params['FROM_CALL_ID'], $params['TO_CALL_ID'], $params['INITIATOR_ID']);

			$output = Json::encode(['result' => 'OK']);
		}
		elseif ($params["COMMAND"] === "StartCall")
		{
			$callId = $params['CALL_ID'];
			$call = \Bitrix\Voximplant\Call::load($callId);
			if (!$call)
			{
				return false;
			}

			$call->handleUserConnected($params['USER_ID'], $params['CALL_DEVICE']);

			\CVoxImplantMain::sendCallStartEvent([
				'CALL_ID' => $call->getCallId(),
				'USER_ID' => $params['USER_ID'],
			]);

			$output = Json::encode(['result' => 'OK']);
		}
		elseif ($params["COMMAND"] === "DetachUser")
		{
			$callId = $params['CALL_ID'];
			$call = \Bitrix\Voximplant\Call::load($callId);
			if ($call)
			{
				$call->removeUsers([$params["USER_ID"]]);
				$output = Json::encode(['result' => 'OK']);
			}
			else
			{
				$output = Json::encode(['ERROR' => 'Call is not found']);
			}
		}
		elseif ($params["COMMAND"] === "HangupCall")
		{
			\CVoxImplantHistory::WriteToLog($params, 'PORTAL HANGUP');
			$call = \Bitrix\Voximplant\Call::load($params['CALL_ID']);
			if ($call)
			{
				$call->finish();
			}
			$output = Json::encode(['result' => 'OK']);
		}
		elseif ($params["COMMAND"] === "SessionTerminated")
		{
			$call = \Bitrix\Voximplant\Call::load($params['CALL_ID']);
			if ($call)
			{
				$call->finish();
			}
			$output = Json::encode(['result' => 'OK']);
		}
		elseif ($params["COMMAND"] === "GetNextAction")
		{
			$call = \Bitrix\Voximplant\Call::load($params['CALL_ID']);
			if ($call)
			{
				if (isset($params['GATHERED_DIGITS']) && $params['GATHERED_DIGITS'] != '')
				{
					$call->updateGatheredDigits($params['GATHERED_DIGITS']);
				}

				$router = new \Bitrix\Voximplant\Routing\Router($call);
				$nextAction = $router->getNextAction($params);
			}
			else
			{
				$nextAction = new \Bitrix\Voximplant\Routing\Action('hangup', ['REASON' => 'Call is not found']);
			}
			$output = $nextAction->toJson();
		}
		elseif ($params["COMMAND"] === "InterceptCall")
		{
			$result = \CVoxImplantIncoming::interceptCall($params['USER_ID'], $params['CALL_ID']);
			$output = Json::encode([
				'RESULT' => $result ? 'Y' : 'N'
			]);
		}
		elseif ($params["COMMAND"] === "InviteUsers")
		{
			$callId = $params["CALL_ID"];
			$users = $params["USERS"];

			$call = \Bitrix\Voximplant\Call::load($callId);
			if ($call)
			{
				$call->getSignaling()->sendInvite($users);
				\Bitrix\Pull\Event::send();
			}
			$output = "OK";
		}
		elseif ($params["COMMAND"] === "Ping")
		{
			$callId = $params["CALL_ID"];
			$call = \Bitrix\Voximplant\Call::load($callId);
			if ($call)
			{
				$call->updateLastPingDate(new \Bitrix\Main\Type\DateTime());
			}
			$output = "Pong";
		}
		elseif ($params['COMMAND'] === 'pingPortal')
		{
			$output = Json::encode(['result' => 'OK']);
		}

		// CONTROLLER OR EMERGENCY HITS
		elseif ($params["BX_COMMAND"] === "add_history" || $params["COMMAND"] === "AddCallHistory")
		{
			\CVoxImplantHistory::WriteToLog($params, 'PORTAL ADD HISTORY');

			if (isset($params['PORTAL_NUMBER'], $params['ACCOUNT_SEARCH_ID']))
			{
				$params['PORTAL_NUMBER'] = $params['ACCOUNT_SEARCH_ID'];
			}

			\CVoxImplantHistory::Add($params);

			if (isset($params["balance"]))
			{
				$ViAccount = new \CVoxImplantAccount();
				$ViAccount->SetAccountBalance($params["balance"]);
			}

			$output = "200 OK";
		}
		elseif ($params["COMMAND"] === "IncomingGetConfig")
		{
			if (isset($params["SIP_HEADERS"]))
			{
				try
				{
					$params["SIP_HEADERS"] = Json::decode($params["SIP_HEADERS"]);
				}
				catch (\Bitrix\Main\ArgumentException $e)
				{
					unset($params["SIP_HEADERS"]);
				}
			}

			$result = \CVoxImplantIncoming::GetConfig($params);

			$output = Json::encode($result);
		}
		elseif ($params["COMMAND"] === "OutgoingGetConfig")
		{
			$phoneNumber = (string)$params['PHONE_NUMBER'];

			$specialNumberHandler = \CVoxImplantOutgoing::getSpecialNumberHandler($phoneNumber);
			if ($specialNumberHandler)
			{
				$result = $specialNumberHandler->getResponse($params['CALL_ID'], $params['USER_ID'], $phoneNumber);
			}
			else
			{
				$lineId = (string)CVoxImplantOutgoing::findLineId($phoneNumber) ?: (string)$params['LINE_ID'];
				$result = \CVoxImplantOutgoing::GetConfig($params['USER_ID'], $lineId);
			}

			\CVoxImplantHistory::WriteToLog($result, 'PORTAL GET OUTGOING CONFIG');

			$output = Json::encode($result);
		}
		elseif ($params["COMMAND"] === "UpdateAccountInfo")
		{
			$accountInfo = (object)$params["ACCOUNT_INFO"];

			$account = new \CVoxImplantAccount();
			$account->UpdateAccountInfo($accountInfo);
			$output = "OK";
		}
		elseif ($params["COMMAND"] === "UploadRecord")
		{
			$callId = $params["CALL_ID"];
			$fileField = $params["FILE_FIELD"];

			$recordFile = Application::getInstance()->getContext()->getRequest()->getFile($fileField);
			if (!is_array($recordFile))
			{
				$answer = [
					"success" => false,
					"errors" => [
						"code" => "NO_FILE",
						"message" => "No file in request"
					]
				];
			}
			else
			{
				$result = \CVoxImplantHistory::AttachRecord($callId, $recordFile);
				$answer = [
					"success" => $result->isSuccess()
				];

				if (!$result->isSuccess())
				{
					$answer["errors"] = $result->getErrors();
				}
			}

			$output = Json::encode($answer);
		}

		// CONTROLLER HITS
		elseif (isset($params['BX_TYPE']))
		{
			$output = "OK";
			if ($params["COMMAND"] === "AddPhoneNumber")
			{
				$output = "DEPRECATED";
			}
			elseif ($params["COMMAND"] === "UnlinkExpirePhoneNumber")
			{
				$output = "DEPRECATED";
			}
			elseif ($params["COMMAND"] === "UpdateOperatorRequest")
			{
				$params['OPERATOR_CONTRACT'] = \Bitrix\Main\Text\Encoding::convertEncodingToCurrent($params['OPERATOR_CONTRACT']);
				\CVoxImplantPhoneOrder::Update($params);

				$result = ['RESULT' => 'OK'];
				\CVoxImplantHistory::WriteToLog($result, 'UPDATE OPERATOR REQUEST');

				$output = Json::encode($result);
			}
			elseif ($params["COMMAND"] === "ExternalHungup")
			{
				$call = \Bitrix\Voximplant\Call::load($params['CALL_ID']);

				if ($call)
				{
					$call->finish([
						'externalHangup' => true,
						'failedCode' => intval($params['CALL_FAILED_CODE'])
					]);
					\CVoxImplantHistory::WriteToLog($call, 'EXTERNAL CALL HANGUP');
				}
			}
			elseif ($params["COMMAND"] === "VerifyResult")
			{
				$params['REVIEWER_COMMENT'] = \Bitrix\Main\Text\Encoding::convertEncodingToCurrent($params['REVIEWER_COMMENT']);

				$ViDocs = new \CVoxImplantDocuments();
				$ViDocs->SetVerifyResult($params);
				$ViDocs->notifyUserWithVerifyResult($params);
			}
			elseif ($params["COMMAND"] === "SetSipStatus")
			{
				$sipStatus = ($params["SIP_PAID"] === 'Y');
				\CVoxImplantConfig::SetModeStatus(CVoxImplantConfig::MODE_SIP, $sipStatus);
				\CVoxImplantHistory::WriteToLog('Sip status set');
			}
			elseif ($params["COMMAND"] === "AddressVerified")
			{
				$addressVerification = new \Bitrix\VoxImplant\AddressVerification();
				$addressVerification->notifyUserWithVerifyResult($params);
			}
			elseif ($params["COMMAND"] === "NotifyAdmins")
			{
				$message = (string)$params["MESSAGE"];
				$buttons = Json::decode($params["BUTTONS"]);
				if (!is_array($buttons))
				{
					$buttons = [];
				}
				\Bitrix\Voximplant\Integration\Im::notifyAdmins($message, $buttons);
			}
			elseif ($params["COMMAND"] === "TranscriptionComplete")
			{
				\Bitrix\Voximplant\Transcript::onTranscriptionComplete([
					'SESSION_ID' => $params['SESSION_ID'],
					'TRANSCRIPTION_URL' => $params['TRANSCRIPTION_URL'],
					'COST' => $params['COST'],
					'COST_CURRENCY' => $params['COST_CURRENCY']
				]);
			}

			elseif ($params["COMMAND"] === "StartCallback")
			{
				$callbackParameters = $params["PARAMETERS"];
				if (!is_array($callbackParameters))
				{
					\CVoxImplantHistory::WriteToLog('Callback parameters is not an array');
				}

				\CVoxImplantOutgoing::restartCallback($callbackParameters);
			}
			elseif ($params["COMMAND"] === "ConferenceFinished")
			{
				$event = new Event("voximplant", "onConferenceFinished", [
					'CONFERENCE_CALL_ID' => $params['PORTAL_CALL_ID'],
					'SESSION_ID' => $params['SESSION_ID'],
					'LOG_URL' => $params['LOG_URL'],
				]);
				$event->send();
			}
			elseif ($params["COMMAND"] === "UpdateSipRegistrations")
			{
				$sipRegistrations = $params['SIP_REGISTRATIONS'];
				$sip = new \CVoxImplantSip();

				$data = $sip->updateSipRegistrations([
					'sipRegistrations' => $sipRegistrations
				]);
			}
		}
		else
		{
			\CVoxImplantHistory::WriteToLog('Command is not found');
			$output = "Requested command is not found.";
		}
	}
	else
	{
		\CVoxImplantHistory::WriteToLog('Request is not authorized');
		$output = "You don't have access to this page.";
	}
}
else
{
	\CVoxImplantHistory::WriteToLog('Could not parse request');
	$output = "Could not parse request";
}

global $APPLICATION;
if ($APPLICATION instanceof \CMain)
{
	$APPLICATION->RestartBuffer();
}

echo $output;


\CMain::FinalActions();
die();


function initParams(): array
{
	return [
		'BX_TYPE' => null,
		'BX_COMMAND' => null,
		'PHONE_NUMBER' => null,
		'ACCOUNT_SEARCH_ID' => null,
		'PORTAL_USER_ID' => null,
		'COMMAND' => null,
		'CALLER_ID' => null,
		'USER_ID' => null,
		'CONFIG_ID' => null,
		'USER_DIRECT_CODE' => null,
		'CALL_ID' => null,
		'CALL_DEVICE' => null,
		'ACCESS_URL' => null,
		'CRM' => null,
		'CRM_ENTITY_TYPE' => null,
		'CRM_ENTITY_ID' => null,
		'CRM_ACTIVITY_ID' => null,
		'CRM_CALL_LIST' => null,
		'CRM_BINDINGS' => null,
		'SESSION_ID' => null,
		'USERS' => null,
		'DEVICE' => null,
		'CODE' => null,
		'REASON' => null,
		'FROM_CALL_ID' => null,
		'TO_CALL_ID' => null,
		'INITIATOR_ID' => null,
		'GATHERED_DIGITS' => null,
		'balance' => null,
		'SIP_HEADERS' => null,
		'LINE_ID' => null,
		'ACCOUNT_INFO' => null,
		'FILE_FIELD' => null,
		'OPERATOR_CONTRACT' => null,
		'REVIEWER_COMMENT' => null,
		'REGION' => null,
		'SIP_PAID' => null,
		'MESSAGE' => null,
		'BUTTONS' => null,
		'TRANSCRIPTION_URL' => null,
		'PARAMETERS' => null,
		'LOG_URL' => null,
		'PORTAL_CALL_ID' => null,
		'SIP_REGISTRATIONS' => null,
		'COST_CURRENCY' => null,
		'CALL_DIRECTION' => null,
		'CALL_LOG' => null,
		'URL' => null,
		'ACCOUNT_PAYED' => null,
		'CALL_FAILED_CODE' => null,
	];
}
