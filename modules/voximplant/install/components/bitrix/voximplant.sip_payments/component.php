<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (!CModule::IncludeModule('voximplant'))
	return;

$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE, \Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
	return;

if(\Bitrix\Voximplant\Limits::isRestOnly())
{
	return;
}

$ViHttp = new CVoxImplantHttp();
$result = $ViHttp->GetSipInfo();

$arResult = array(
	'FREE' => intval($result->FREE),
	'ACTIVE' => $result->ACTIVE,
	'DATE_END' => ($result->DATE_END <> '' ? new \Bitrix\Main\Type\Date($result->DATE_END, 'd.m.Y') : ''),
);

if ($result->ACTIVE != CVoxImplantConfig::GetModeStatus(CVoxImplantConfig::MODE_SIP))
{
	CVoxImplantConfig::SetModeStatus(CVoxImplantConfig::MODE_SIP, $result->ACTIVE? true: false);
}

$arResult['LINK_TO_BUY'] = '';
if (IsModuleInstalled('bitrix24'))
{
	if (LANGUAGE_ID != 'kz')
	{
		$arResult['LINK_TO_BUY'] = '/settings/license_phone_sip.php';
	}
}
else
{
	if (LANGUAGE_ID == 'ru')
	{
		$arResult['LINK_TO_BUY'] = 'http://www.1c-bitrix.ru/buy/intranet.php#tab-call-link';
	}
	else if (LANGUAGE_ID == 'ua')
	{
		$arResult['LINK_TO_BUY'] = 'http://www.1c-bitrix.ua/buy/intranet.php#tab-call-link';
	}
	else if (LANGUAGE_ID == 'kz')
	{
	}
	else if (LANGUAGE_ID == 'de')
	{
		$arResult['LINK_TO_BUY'] = 'https://www.bitrix24.de/prices/self-hosted-telephony.php';
	}
	else
	{
		$arResult['LINK_TO_BUY'] = 'https://www.bitrix24.com/prices/self-hosted-telephony.php';
	}
}

$viAccount = new CVoxImplantAccount();
$arResult['ACCOUNT_NAME'] = $viAccount->GetAccountName();

$arResult['SIP_NOTICE_OLD_CONFIG_OFFICE_PBX'] = CVoxImplantConfig::GetNoticeOldConfigOfficePbx();

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>