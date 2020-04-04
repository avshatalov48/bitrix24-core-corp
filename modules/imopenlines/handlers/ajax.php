<?
if($_SERVER["REQUEST_METHOD"] == "POST" && array_key_exists("IM_AJAX_CALL", $_REQUEST) && $_REQUEST["IM_AJAX_CALL"] === "Y" && $_POST['IM_OPEN_LINES_CLIENT'] == 'Y')
{
	$chatId = intval($_POST['CHAT_ID']);
	$userId = intval($USER->GetId());

	if ($userId <= 0 || !(IsModuleInstalled('imopenlines') && \Bitrix\Main\Loader::includeModule('im') && \Bitrix\Im\User::getInstance($userId)->isConnector()))
	{
		echo \Bitrix\ImOpenLines\Common::objectEncode(Array('ERROR' => 'AUTHORIZE_ERROR'));
		CMain::FinalActions();
		die();
	}

	if (!check_bitrix_sessid())
	{
		echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
			'BITRIX_SESSID' => bitrix_sessid(),
			'ERROR' => 'SESSION_ERROR'
		));
		CMain::FinalActions();
		die();
	}

	if ($_POST['COMMAND'] == "sendLivechatForm")
	{
		$params = Array();

		CUtil::decodeURIComponent($_POST);

		$control = new \Bitrix\ImOpenLines\Widget\Form($chatId, $userId);
		$result = $control->saveForm($_POST['FORM'], $_POST['FIELDS']);
		if ($result->isSuccess())
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'ERROR' => ''
			));
		}
		else
		{
			$errors = $result->getErrors();
			$error = current($errors);

			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $error->getCode(),
				'ERROR' => $error->getMessage()
			));
		}
	}
}
else if($_SERVER["REQUEST_METHOD"] == "POST" && array_key_exists("IM_AJAX_CALL", $_REQUEST) && $_REQUEST["IM_AJAX_CALL"] === "Y" && $_POST['IM_OPEN_LINES'] == 'Y')
{
	if (intval($USER->GetID()) <= 0 || !(IsModuleInstalled('imopenlines') && (!IsModuleInstalled('extranet') || CModule::IncludeModule('extranet') && CExtranet::IsIntranetUser())))
	{
		echo \Bitrix\ImOpenLines\Common::objectEncode(Array('ERROR' => 'AUTHORIZE_ERROR'));
		CMain::FinalActions();
		die();
	}

	if (!check_bitrix_sessid())
	{
		echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
			'BITRIX_SESSID' => bitrix_sessid(),
			'ERROR' => 'SESSION_ERROR'
		));
		CMain::FinalActions();
		die();
	}

	$chatId = intval($_POST['CHAT_ID']);
	$userId = intval($USER->GetId());

	if ($_POST['COMMAND'] == 'answer')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->answer();
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
	else if ($_POST['COMMAND'] == 'skip')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->skip();
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
	else if ($_POST['COMMAND'] == 'transfer')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->transfer(Array(
			'TRANSFER_ID' => $_POST['TRANSFER_ID'],
		));
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
	else if ($_POST['COMMAND'] == 'silentMode')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->setSilentMode($_POST['ACTIVATE'] == 'Y');
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
	else if ($_POST['COMMAND'] == 'pinMode')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->setPinMode($_POST['ACTIVATE'] == 'Y');
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
	else if ($_POST['COMMAND'] == 'closeDialog')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->closeDialog();
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
	else if ($_POST['COMMAND'] == 'markSpam')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->markSpam();
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
	else if ($_POST['COMMAND'] == 'interceptSession')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->interceptSession();
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
	else if ($_POST['COMMAND'] == 'createLead')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->createLead();
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
	else if ($_POST['COMMAND'] == 'cancelCrmExtend')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->cancelCrmExtend($_POST['MESSAGE_ID']);
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
	else if ($_POST['COMMAND'] == 'changeCrmEntity')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->changeCrmEntity($_POST['MESSAGE_ID'], strtoupper($_POST['ENTITY_TYPE']), $_POST['ENTITY_ID']);
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
	else if ($_POST['COMMAND'] == 'openSession')
	{
		$control = new \Bitrix\ImOpenLines\Operator(0, $userId);
		$result = $control->openChat($_POST['USER_CODE']);
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CHAT_ID' => $result['ID'],
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
	else if ($_POST['COMMAND'] == 'voteHead')
	{
		CUtil::decodeURIComponent($_POST);

		$control = new \Bitrix\ImOpenLines\Operator(0, $userId);
		$result = $control->voteAsHead($_POST['SESSION_ID'], $_POST['RATING'], $_POST['COMMENT']);
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
	else if ($_POST['COMMAND'] == 'joinSession')
	{
		$control = new \Bitrix\ImOpenLines\Operator($_POST['CHAT_ID'], $userId);
		$result = $control->joinSession();
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CHAT_ID' => $_POST['CHAT_ID'],
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
	else if ($_POST['COMMAND'] == 'startSession')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->startSession();
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
	else if ($_POST['COMMAND'] == 'startSessionByMessage')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->startSessionByMessage($_POST['MESSAGE_ID']);
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
	else if ($_POST['COMMAND'] == 'saveToQuickAnswers')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->saveToQuickAnswers($_POST['MESSAGE_ID']);
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
	else if ($_POST['COMMAND'] == 'sessionGetHistory')
	{
		$control = new \Bitrix\ImOpenLines\Operator(0, $userId);
		$result = $control->getSessionHistory($_POST['SESSION_ID']);

		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CHAT_ID' => $result['chatId'],
				'CAN_JOIN' => $result['canJoin'],
				'CAN_VOTE_HEAD' => $result['canVoteAsHead'],
				'SESSION_ID' => $result['sessionId'],
				'SESSION_VOTE_HEAD' => $result['sessionVoteHead'],
				'SESSION_COMMENT_HEAD' => $result['sessionCommentHead'],
				'USER_ID' => 'chat'.$result['chatId'],
				'MESSAGE' => isset($result['message'])? $result['message']: Array(),
				'USERS_MESSAGE' => isset($result['message'])? $result['usersMessage']: Array(),
				'USERS' => isset($result['users'])? $result['users']: Array(),
				'OPENLINES' => isset($result['openlines'])? $result['openlines']: Array(),
				'USER_IN_GROUP' => isset($result['userInGroup'])? $result['userInGroup']: Array(),
				'WO_USER_IN_GROUP' => isset($result['woUserInGroup'])? $result['woUserInGroup']: Array(),
				'CHAT' => isset($result['chat'])? $result['chat']: Array(),
				'USER_BLOCK_CHAT' => isset($result['userChatBlockStatus'])? $result['userChatBlockStatus']: Array(),
				'USER_IN_CHAT' => isset($result['userInChat'])? $result['userInChat']: Array(),
				'FILES' => isset($result['files'])? $result['files']: Array(),
				'ERROR' => ''
			));
		}
		else
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CODE' => $control->getError()->code,
				'ERROR' => $control->getError()->msg
			));
		}
	}
}

CMain::FinalActions();
die();
?>