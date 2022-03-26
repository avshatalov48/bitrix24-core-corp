<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (!CModule::IncludeModule('voximplant'))
	return;

$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_SETTINGS, \Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
{
	ShowError(GetMessage('COMP_VI_ACCESS_DENIED'));
	return;
}

$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();


$account = new CVoxImplantAccount();

$arResult['IS_REST_ONLY'] = \Bitrix\Voximplant\Limits::isRestOnly();
$arResult['LINES'] = CVoxImplantConfig::GetPortalNumbers(true, true);
$arResult['CURRENT_LINE'] = CVoxImplantConfig::GetPortalNumber();

$arResult['BACKUP_NUMBER'] = CVoxImplantConfig::getCommonBackupNumber();
$arResult['BACKUP_LINE'] = CVoxImplantConfig::getCommonBackupLine();

$arResult['INTERFACE_CHAT_OPTIONS'] = array(CVoxImplantConfig::INTERFACE_CHAT_ADD, CVoxImplantConfig::INTERFACE_CHAT_APPEND, CVoxImplantConfig::INTERFACE_CHAT_NONE);
$arResult['INTERFACE_CHAT_ACTION'] = CVoxImplantConfig::GetChatAction();

$arResult['LEAD_ENABLED'] = CVoxImplantCrmHelper::isLeadEnabled();
$arResult['WORKFLOW_OPTIONS'] = array(
	CVoxImplantConfig::WORKFLOW_START_IMMEDIATE => GetMessage("VI_CRM_INTEGRATION_WORKFLOW_EXECUTION_IMMEDIATE"),
	CVoxImplantConfig::WORKFLOW_START_DEFERRED => GetMessage("VI_CRM_INTEGRATION_WORKFLOW_EXECUTION_DEFERRED")
);
$arResult['WORKFLOW_OPTION'] = CVoxImplantConfig::GetLeadWorkflowExecution();

$arResult['COMBINATION_INTERCEPT_GROUP'] = CVoxImplantConfig::GetCombinationInterceptGroup();

if ($request->isPost() && check_bitrix_sessid())
{
	if($request['CURRENT_LINE'] != $arResult['CURRENT_LINE'])
	{
		CVoxImplantConfig::SetPortalNumber($request['CURRENT_LINE']);
	}
	if($request['INTERFACE_CHAT_ACTION'] != $arResult['INTERFACE_CHAT_ACTION'])
	{
		CVoxImplantConfig::SetChatAction($request['INTERFACE_CHAT_ACTION']);
	}
	if($arResult['LEAD_ENABLED'] && $request['WORKFLOW_OPTION'] != $arResult['WORKFLOW_OPTION'])
	{
		CVoxImplantConfig::SetLeadWorkflowExecution($request['WORKFLOW_OPTION']);
	}

	if(!$arResult['IS_REST_ONLY'])
	{
		if($request['BACKUP_NUMBER'] != $arResult['BACKUP_NUMBER'] || $request['BACKUP_LINE'] != $arResult['BACKUP_LINE'])
		{
			CVoxImplantConfig::setCommonBackupNumber($request['BACKUP_NUMBER'], $request['BACKUP_LINE']);
		}
		if($request['COMBINATION_INTERCEPT_GROUP'] != $arResult['COMBINATION_INTERCEPT_GROUP'])
		{
			CVoxImplantConfig::SetCombinationInterceptGroup($request['COMBINATION_INTERCEPT_GROUP']);
		}
	}

	LocalRedirect($request->getRequestUri());
	return;
}


if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;
