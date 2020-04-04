<? use Bitrix\Mobile\Auth;

if (!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $USER;

if (intval($USER->GetID()) <= 0)
{
	Auth::setNotAuthorizedHeaders();
	$result = Array(
		'ERROR' => 'AUTHORIZE_ERROR',
		'BITRIX_SESSID' => bitrix_sessid()
	);
}

if ($USER->IsJustAuthorized())
{
	if(
		CModule::IncludeModule('im') && isset($_REQUEST['RECIPIENT_ID']) && isset($_REQUEST['MESSAGE'])
	)
	{
		$_POST = \Bitrix\Main\Text\Encoding::convertEncodingArray($_POST, 'UTF-8', SITE_CHARSET);

		$arParams = false;
		$userId = intval($USER->GetID());
		if (substr($_REQUEST['RECIPIENT_ID'], 0, 4) == 'chat')
		{
			$chatId = intval(substr($_REQUEST['RECIPIENT_ID'], 4));
			if (CIMChat::GetGeneralChatId() != $chatId || !CIMChat::CanSendMessageToGeneralChat($userId))
			{
				$arParams = Array(
					"FROM_USER_ID" => $userId,
					"TO_CHAT_ID" => $chatId,
					"MESSAGE" 	 => $_POST['MESSAGE'],
					"MESSAGE_TYPE" => IM_MESSAGE_CHAT
				);
			}
		}
		else
		{
			$arParams = Array(
				"FROM_USER_ID" => $userId,
				"TO_USER_ID" => intval($_REQUEST['RECIPIENT_ID']),
				"MESSAGE" 	 => $_POST['MESSAGE'],
			);
		}
		if ($arParams)
		{
			CIMMessage::Add($arParams);
			$result = Array('RESULT' => 'OK');
		}
		else
		{
			$result = Array('RESULT' => 'AUTHORIZE_ERROR');
		}
	}
	else
	{
		$result = Array('ERROR' => 'PARAMS ERROR');
	}
}
else
{
	$result = Array(
		'ERROR' => 'SESSION_ERROR',
		'BITRIX_SESSID' => bitrix_sessid(),
	);
}

return $result;