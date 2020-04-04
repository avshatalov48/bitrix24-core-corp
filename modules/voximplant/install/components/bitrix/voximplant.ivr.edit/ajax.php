<?
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NOT_CHECK_PERMISSIONS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!CModule::IncludeModule("voximplant"))
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'VI_MODULE_NOT_INSTALLED'));
	CMain::FinalActions();
	die();
}

if (!check_bitrix_sessid())
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'SESSION_ERROR'));
	CMain::FinalActions();
	die();
}

$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_SETTINGS,\Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'AUTHORIZE_ERROR'));
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
	CBitrixComponent::includeComponentClass('bitrix:voximplant.ivr.edit');
	$result = CVoximplantIvrEditComponent::saveIvr(\Bitrix\Main\Web\Json::decode($_POST['IVR']));
	$sendResult($result);
}
else if ($action == 'upload_file')
{
	CBitrixComponent::includeComponentClass('bitrix:voximplant.ivr.edit');
	$result = CVoximplantIvrEditComponent::uploadFile($_POST);
	$sendResult($result);
}
else if ($action == 'group_settings')
{
	$APPLICATION->ShowAjaxHead();
	$APPLICATION->IncludeComponent(
		'bitrix:voximplant.queue.edit',
		'',
		array(
			'ID' => (int)$_REQUEST['groupId'],
			'INLINE_MODE' => true,
			'EXTERNAL_REQUEST_ID' => (string)$_REQUEST['requestId']
		)
	);
}
else
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'UNKNOWN_ACTION'));
}

CMain::FinalActions();
die();