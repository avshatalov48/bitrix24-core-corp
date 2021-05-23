<?if (!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $USER;

if (intval($USER->GetID()) <= 0)
{
	\Bitrix\Mobile\Auth::setNotAuthorizedHeaders();

	$result = Array(
		'ERROR' => 'AUTHORIZE_ERROR',
		'BITRIX_SESSID' => bitrix_sessid()
	);
}

if ($USER->IsJustAuthorized())
{
	if(
		CModule::IncludeModule('tasks') && isset($_REQUEST['TASK_ID']) && isset($_REQUEST['MESSAGE'])
	)
	{
		$post = \Bitrix\Main\Text\Encoding::convertEncodingArray($_POST, 'UTF-8', SITE_CHARSET);

		try
		{
			CTaskNotifications::addAnswer($post['TASK_ID'], $post['MESSAGE']);
			$result = Array('RESULT' => 'OK');
		}
		catch(Exception $e)
		{
			$result = Array('ERROR' => 'INTERNAL');
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