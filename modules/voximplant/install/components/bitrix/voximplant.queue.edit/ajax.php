<?
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NOT_CHECK_PERMISSIONS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Web\Json;

if (!CModule::IncludeModule("voximplant"))
{
	echo Json::encode(array('ERROR' => 'VI_MODULE_NOT_INSTALLED'));
	CMain::FinalActions();
	die();
}

if (!check_bitrix_sessid())
{
	echo Json::encode(array('ERROR' => 'SESSION_ERROR'));
	CMain::FinalActions();
	die();
}

$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_SETTINGS,\Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
{
	echo Json::encode(array('ERROR' => 'AUTHORIZE_ERROR'));
	CMain::FinalActions();
	die();
}

$action = $_REQUEST['action'];

$sendResult = function(\Bitrix\Main\Result $result)
{
	if($result->isSuccess())
	{
		echo \Bitrix\Main\Web\Json::encode(array(
			'SUCCESS' => true,
			'DATA' => $result->getData()
		));
	}
	else
	{
		echo \Bitrix\Main\Web\Json::encode(array(
			'SUCCESS' => false,
			'ERROR' => implode(';', $result->getErrorMessages())
		));
	}
};

if($action == 'save')
{
	CBitrixComponent::includeComponentClass('bitrix:voximplant.queue.edit');
	$request = \Bitrix\Main\Text\Encoding::convertEncoding($_POST, 'utf8', SITE_CHARSET);
	$result = CVoximplantQueueEditComponent::save($request);
	$sendResult($result);
}
else
{
	echo Json::encode(array('ERROR' => 'UNKNOWN_ACTION'));
}

CMain::FinalActions();
die();