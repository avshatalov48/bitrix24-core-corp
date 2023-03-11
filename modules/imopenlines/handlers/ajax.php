<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$request = Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

if ($request->isPost() &&
	$request->get('IM_AJAX_CALL') === 'Y' &&
	$request->getPost('IM_OPEN_LINES_CLIENT') == 'Y'
)
{
	$chatId = intval($request->getPost('CHAT_ID'));
	$userId = intval($USER->GetId());

	if ($userId <= 0 || !(\Bitrix\Main\ModuleManager::isModuleInstalled('imopenlines') && \Bitrix\Main\Loader::includeModule('im') && \Bitrix\Im\User::getInstance($userId)->isConnector()))
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
}
else if($request->isPost() &&
	$request->get('IM_AJAX_CALL') === 'Y' &&
	$request->getPost('IM_OPEN_LINES') == 'Y'
)
{
	if (intval($USER->GetID()) <= 0 || !(\Bitrix\Main\ModuleManager::isModuleInstalled('imopenlines') && (!\Bitrix\Main\ModuleManager::isModuleInstalled('extranet') || \Bitrix\Main\Loader::includeModule('extranet') && CExtranet::IsIntranetUser())))
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

	$chatId = intval($request->getPost('CHAT_ID'));
	$userId = intval($USER->GetId());

	if ($request->getPost('COMMAND') == 'answer')
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
	//skip the dialogue
	else if ($request->getPost('COMMAND') == 'skip')
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
	else if ($request->getPost('COMMAND') == 'transfer')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->transfer(Array(
			'TRANSFER_ID' => $request->getPost('TRANSFER_ID'),
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
	else if ($request->getPost('COMMAND') == 'silentMode')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->setSilentMode($request->getPost('ACTIVATE') == 'Y');
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
	else if ($request->getPost('COMMAND') == 'pinMode')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->setPinMode($request->getPost('ACTIVATE') == 'Y');
		if ($result->isSuccess())
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode([
				'ERROR' => ''
			]);
		}
		else
		{
			$errors = $result->getErrors();
			$error = current($errors);

			echo \Bitrix\ImOpenLines\Common::objectEncode([
				'CODE' => $error->getCode(),
				'ERROR' => $error->getMessage()
			]);
		}
	}
	else if ($request->getPost('COMMAND') == 'closeDialog')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->closeDialog();
		if ($result->isSuccess())
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode([
				'ERROR' => ''
			]);
		}
		else
		{
			$errors = $result->getErrors();
			$error = current($errors);

			echo \Bitrix\ImOpenLines\Common::objectEncode([
				'CODE' => $error->getCode(),
				'ERROR' => $error->getMessage()
			]);
		}
	}
	else if ($request->getPost('COMMAND') == 'closeDialogOtherOperator')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->closeDialogOtherOperator();
		if ($result->isSuccess())
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode([
				'ERROR' => ''
			]);
		}
		else
		{
			$errors = $result->getErrors();
			$error = current($errors);

			echo \Bitrix\ImOpenLines\Common::objectEncode([
				'CODE' => $error->getCode(),
				'ERROR' => $error->getMessage()
			]);
		}
	}
	else if ($request->getPost('COMMAND') == 'markSpam')
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
	else if ($request->getPost('COMMAND') == 'interceptSession')
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
	else if ($request->getPost('COMMAND') == 'createLead')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->createLead();
		if ($result->isSuccess())
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode([
				'ERROR' => ''
			]);
		}
		else
		{
			$errors = $result->getErrors();
			$error = current($errors);

			echo \Bitrix\ImOpenLines\Common::objectEncode([
				'CODE' => $error->getCode(),
				'ERROR' => $error->getMessage()
			]);
		}
	}
	else if ($request->getPost('COMMAND') == 'cancelCrmExtend')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->cancelCrmExtend($request->getPost('MESSAGE_ID'));
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
	else if ($request->getPost('COMMAND') == 'changeCrmEntity')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->changeCrmEntity($request->getPost('MESSAGE_ID'), mb_strtoupper($request->getPost('ENTITY_TYPE')), $request->getPost('ENTITY_ID'));
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
	else if ($request->getPost('COMMAND') == 'openSession')
	{
		$control = new \Bitrix\ImOpenLines\Operator(0, $userId);
		$userCode = $request->getPostList()->getRaw('USER_CODE');
		$result = $control->openChat($userCode);
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
	else if ($request->getPost('COMMAND') == 'voteHead')
	{
		$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter());

		$control = new \Bitrix\ImOpenLines\Operator(0, $userId);
		$result = $control->voteAsHead($request->getPost('SESSION_ID'), $request->getPost('RATING'), $request->getPost('COMMENT'));
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
	else if ($request->getPost('COMMAND') == 'joinSession')
	{
		$control = new \Bitrix\ImOpenLines\Operator($request->getPost('CHAT_ID'), $userId);
		$result = $control->joinSession();
		if ($result)
		{
			echo \Bitrix\ImOpenLines\Common::objectEncode(Array(
				'CHAT_ID' => $request->getPost('CHAT_ID'),
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
	else if ($request->getPost('COMMAND') == 'startSession')
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
	else if ($request->getPost('COMMAND') == 'startSessionByMessage')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->startSessionByMessage($request->getPost('MESSAGE_ID'));
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
	else if ($request->getPost('COMMAND') == 'saveToQuickAnswers')
	{
		$control = new \Bitrix\ImOpenLines\Operator($chatId, $userId);
		$result = $control->saveToQuickAnswers($request->getPost('MESSAGE_ID'));
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
	else if ($request->getPost('COMMAND') == 'sessionGetHistory')
	{
		$control = new \Bitrix\ImOpenLines\Operator(0, $userId);
		$result = $control->getSessionHistory($request->getPost('SESSION_ID'));

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