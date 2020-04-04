<?
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NOT_CHECK_PERMISSIONS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!CModule::IncludeModule("voximplant"))
{
	echo CUtil::PhpToJsObject(Array('SUCCESS' => false, 'ERROR' => 'VI_MODULE_NOT_INSTALLED'));
	CMain::FinalActions();
	die();
}

if (!check_bitrix_sessid())
{
	echo CUtil::PhpToJsObject(Array('SUCCESS' => false, 'ERROR' => 'SESSION_ERROR'));
	CMain::FinalActions();
	die();
}

$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_SETTINGS,\Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
{
	echo CUtil::PhpToJsObject(Array('SUCCESS' => false, 'ERROR' => 'AUTHORIZE_ERROR'));
	CMain::FinalActions();
	die();
}

$action = $_REQUEST['action'];
if($action == 'delete')
{
	$queueId = (int)$_POST['id'];
	$queue = \Bitrix\Voximplant\Queue::createWithId($queueId);
	$usages = ($queue instanceof \Bitrix\Voximplant\Queue) ? $queue->findUsages() : 0;
	if(count($usages) > 0)
	{
		$result = array(
			'SUCCESS' => false,
			'USAGES' => $usages
		);
	}
	else
	{
		//don't delete yet
		\Bitrix\Voximplant\Model\QueueTable::delete($queueId);
		$result = array('SUCCESS' => true);
	}

	echo\Bitrix\Main\Web\Json::encode($result);
}
else
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'UNKNOWN_ACTION'));
}

CMain::FinalActions();
die();