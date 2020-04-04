<?
/**
 * @global $USER
 * @global $APPLICATION
 */
if($_SERVER["REQUEST_METHOD"] == "POST" && array_key_exists("IM_AJAX_CALL", $_REQUEST) && $_REQUEST["IM_AJAX_CALL"] === "Y" && $_POST['IM_PHONE'] == 'Y')
{
	if (intval($USER->GetID()) <= 0 || !(IsModuleInstalled('voximplant') && (!IsModuleInstalled('extranet') || CModule::IncludeModule('extranet') && CExtranet::IsIntranetUser())))
	{
		echo CUtil::PhpToJsObject(Array('ERROR' => 'AUTHORIZE_ERROR'));
		CMain::FinalActions();
		die();
	}

	if (check_bitrix_sessid())
	{
		$APPLICATION->RestartBuffer();

		IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/voximplant/ajax_hit.php');

		$chatId = intval($_POST['CHAT_ID']);
		$userId = intval($USER->GetId());

		if ($_POST['COMMAND'] == 'authorize')
		{
			$updateInfo = $_POST['UPDATE_INFO'] == 'Y';
			$ViMain = new CVoxImplantMain($userId);
			$result = $ViMain->GetAuthorizeInfo($updateInfo);
			if (!$result)
			{
				echo CUtil::PhpToJsObject(Array(
					'CODE' => $ViMain->GetError()->code,
					'ERROR' => $ViMain->GetError()->msg
				));
			}
			else
			{
				echo CUtil::PhpToJsObject(Array(
					'ACCOUNT' => $result['ACCOUNT'],
					'SERVER' => $result['SERVER'],
					'LOGIN' => $result['LOGIN'],
					'CALLERID' => $result['CALLERID'],
					'HASH' => $result['HASH'],
					'HR_PHOTO' => $result['HR_PHOTO'],
					'ERROR' => ''
				));
			}
		}
		else if ($_POST['COMMAND'] == 'onetimekey')
		{
			$ViMain = new CVoxImplantMain($userId);
			$result = $ViMain->GetOneTimeKey($_POST['KEY']);
			if (!$result)
			{
				echo CUtil::PhpToJsObject(Array(
					'CODE' => $ViMain->GetError()->code,
					'ERROR' => $ViMain->GetError()->msg
				));
			}
			else
			{
				echo CUtil::PhpToJsObject(Array(
					'HASH' => $result,
					'ERROR' => ''
				));
			}
		}
		else if ($_POST['COMMAND'] == 'authorize_error')
		{
			$ViMain = new CVoxImplantMain($userId);
			$ViMain->ClearUserInfo();
			$ViMain->ClearAccountInfo();
		}
		else if ($_POST['COMMAND'] == 'init')
		{
			$ViMain = new CVoxImplantMain($userId);
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
		else if ($_POST['COMMAND'] == 'deviceStartCall')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			$number = $_POST['PARAMS']['NUMBER'];
			$params = $_POST['PARAMS']['PARAMS'];
			if (CVoxImplantUser::GetPhoneActive($USER->GetId()))
			{
				$result = CVoxImplantOutgoing::StartCall($USER->GetId(), $number, $params);
				echo \Bitrix\Main\Web\Json::encode($result);
			}
		}
		else if ($_POST['COMMAND'] == 'deviceHungup')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			$call = \Bitrix\Voximplant\Call::load($_POST['PARAMS']['CALL_ID']);
			if($call)
			{
				$call->getScenario()->sendCancelExternalCall($userId);
			}
		}
		else if ($_POST['COMMAND'] == 'wait')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);

			CVoxImplantIncoming::SendCommand(Array(
				'CALL_ID' => $_POST['PARAMS']['CALL_ID'],
				'COMMAND' => CVoxImplantIncoming::RULE_WAIT,
				'DEBUG_INFO' => $_POST['PARAMS']['DEBUG_INFO']
			));
		}
		else if ($_POST['COMMAND'] == 'answer')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			$callId = $_POST['PARAMS']['CALL_ID'];
			$call = \Bitrix\Voximplant\Call::load($callId);
			if($call)
			{
				try
				{
					$call->handleUserAnswer($userId);
				}
				catch (Exception $e)
				{
					echo CUtil::PhpToJsObject(Array(
						'CODE' => $e->getCode(),
						'ERROR' => $e->getMessage()
					));
				}
			}
		}
		else if ($_POST['COMMAND'] == 'skip')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);

			CVoxImplantIncoming::SendCommand(Array(
				'CALL_ID' => $_POST['PARAMS']['CALL_ID'],
				'COMMAND' => CVoxImplantIncoming::RULE_QUEUE
			));
		}
		else if($_POST['COMMAND'] == 'busy')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			$callId = $_POST['PARAMS']['CALL_ID'];

			CVoxImplantIncoming::SendCommand(Array(
				'CALL_ID' => $callId,
				'COMMAND' => CVoxImplantIncoming::COMMAND_BUSY
			));
		}
		else if ($_POST['COMMAND'] == 'hold')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			$callId = $_POST['PARAMS']['CALL_ID'];

			$call = \Bitrix\Voximplant\Call::load($callId);
			if($call)
			{
				$call->getScenario()->sendHold($userId);;
				$call->getSignaling()->sendHold($userId);
			}
		}
		else if($_POST['COMMAND'] == 'unhold')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			$callId = $_POST['PARAMS']['CALL_ID'];

			$call = \Bitrix\Voximplant\Call::load($callId);
			if($call)
			{
				$call->getScenario()->sendUnHold($userId);;
				$call->getSignaling()->sendUnHold($userId);
			}
		}
		else if ($_POST['COMMAND'] == 'ready')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);

			CVoxImplantIncoming::SendCommand(Array(
				'CALL_ID' => $_POST['PARAMS']['CALL_ID'],
				'COMMAND' => CVoxImplantIncoming::RULE_USER,
				'USER_ID' => $USER->GetId(),
			));
		}
		else if ($_POST['COMMAND'] == 'connectionError')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			$callId = $_POST['PARAMS']['CALL_ID'];
			$error = $_POST['PARAMS']['ERROR'];

			$call = \Bitrix\Voximplant\Call::load($callId);
			if($call)
			{
				$call->getScenario()->sendConnectionError($userId, $error);
			}
		}
		else if ($_POST['COMMAND'] == 'startTransfer')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			$result = Bitrix\Voximplant\Transfer\Transferor::initiateTransfer(
				$_POST['PARAMS']['CALL_ID'],
				$userId,
				$_POST['PARAMS']['TARGET_TYPE'],
				$_POST['PARAMS']['TARGET_ID']
			);

			echo $result->toJson();
		}
		else if ($_POST['COMMAND'] == 'completeTransfer')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			$call = \Bitrix\Voximplant\Call::load($_POST['PARAMS']['CALL_ID']);
			if($call)
			{
				$call->getScenario()->sendCompleteTransfer($userId);
			}
		}
		else if ($_POST['COMMAND'] == 'cancelTransfer')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);

			$transferCall = \Bitrix\Voximplant\Call::load($_POST['PARAMS']['CALL_ID']);
			if($transferCall)
			{
				$transferCall->getScenario()->sendCancelTransfer($userId);
			}
		}
		else if ($_POST['COMMAND'] == 'startCallViaRest')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			\Bitrix\Voximplant\Rest\Helper::startCall($_POST['PARAMS']['NUMBER'], $userId, $_POST['PARAMS']['LINE_ID'], $_POST['PARAMS']['PARAMS']);
		}
		else if($_POST['COMMAND'] == 'getCall')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			$callId = $_POST['PARAMS']['CALL_ID'];
			$call = \Bitrix\Voximplant\Model\CallTable::getByCallId($callId);

			if($call)
			{
				$result = array(
					'FOUND' => 'Y',
					'CALL' => $call
				);
			}
			else
			{
				$result = array(
					'FOUND' => 'N'
				);
			}

			echo \Bitrix\Main\Web\Json::encode($result);
		}
		else if ($_POST['COMMAND'] == 'getCrmCard')
		{
			if(!\Bitrix\Main\Loader::includeModule('crm'))
				return false;

			$APPLICATION->ShowAjaxHead();
			$APPLICATION->IncludeComponent('bitrix:crm.card.show',
				'',
				array(
					'ENTITY_TYPE' => $_POST['PARAMS']['ENTITY_TYPE'],
					'ENTITY_ID' => (int)$_POST['PARAMS']['ENTITY_ID'],
				)
			);
		}
		else if($_POST['COMMAND'] == 'interceptCall')
		{
			$interceptResult = false;
			$callId = CVoxImplantIncoming::findCallToIntercept($userId);
			if($callId)
			{
				$interceptResult = CVoxImplantIncoming::interceptCall($userId, $callId);
			}

			$result = array(
				'FOUND' => $interceptResult ? 'Y' : 'N'
			);
			if (!$interceptResult)
			{
				$result['ERROR'] = GetMessage('VOX_CALL_FOR_INTERCEPT_NOT_FOUND');
			}

			echo \Bitrix\Main\Web\Json::encode($result);
		}
		else if($_POST['COMMAND'] == 'saveComment')
		{
			$params =  \Bitrix\Main\Web\Json::decode($_POST['PARAMS']);
			$callId = $params['CALL_ID'];
			$comment = $params['COMMENT'];
			$call = \Bitrix\Voximplant\Call::load($callId);
			if($call)
			{
				$call->setComment($comment);
			}
			else
			{
				CVoxImplantHistory::saveComment($callId, $comment);
			}
		}
	}
	else
	{
		echo CUtil::PhpToJsObject(Array(
			'BITRIX_SESSID' => bitrix_sessid(),
			'ERROR' => 'SESSION_ERROR'
		));
	}
}

CMain::FinalActions();
die();
?>