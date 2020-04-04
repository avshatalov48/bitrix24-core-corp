<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}
$arResult = array(
	'activity' => $arParams['~ACTIVITY']
);
$activity = $arResult['activity'];
$params = $activity['PROVIDER_PARAMS'];
/*
$params => array(
	'sender' => 'robot',
	'recipient_user_id' => $recipientUserId,
),
 */

$arResult['IS_ROBOT'] = isset($params['sender']) && $params['sender'] === 'robot';
$arResult['SMS_MESSAGE'] = \Bitrix\Crm\Integration\SmsManager::getMessageFields($activity['ASSOCIATED_ENTITY_ID']);
$arResult['SMS_MESSAGE_STATUS_DESCRIPTIONS'] = \Bitrix\Crm\Integration\SmsManager::getMessageStatusDescriptions();
$arResult['SMS_MESSAGE_STATUS_IS_ERROR'] = \Bitrix\Crm\Integration\SmsManager::isMessageErrorStatus($arResult['SMS_MESSAGE']['STATUS_ID']);

if (!empty($params['recipient_user_id']))
{
	$arResult['recipientName'] = CCrmViewHelper::GetFormattedUserName(
		$params['recipient_user_id'],
		$arParams['NAME_TEMPLATE']
	);
	$arResult['recipientUrl'] = CComponentEngine::MakePathFromTemplate(
		'/company/personal/user/#user_id#/',
		array('user_id' => $params['recipient_user_id'])
	);
}
$this->IncludeComponentTemplate();
return $arResult;
