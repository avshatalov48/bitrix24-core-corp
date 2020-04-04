<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (!CModule::IncludeModule('voximplant'))
	return;

$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE, \Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
	return;

$arResult = Array();

$ViHttp = new CVoxImplantHttp();
$result = $ViHttp->GetSipInfo();

$arResult['SIP_ENABLE'] = (bool)$result->ACTIVE;
$arResult['TEST_MINUTES'] = intval($result->FREE);
$arResult['DATE_END'] = (strlen($result->DATE_END) > 0 ? new \Bitrix\Main\Type\Date($result->DATE_END, 'd.m.Y') : '');

$arResult['LINK_TO_BUY'] = CVoxImplantSip::getBuyLink();
if (IsModuleInstalled('bitrix24'))
{
	$account = new CVoxImplantAccount();
	$accountLang = $account->GetAccountLang();
	$arResult['LINK_TO_DOC'] = (in_array(LANGUAGE_ID, Array("ru", "kz", "ua", "by"))? 'https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=52&CHAPTER_ID=02564': 'https://www.bitrixsoft.com/support/training/course/index.php?COURSE_ID=55&LESSON_ID=6635');
}
else
{
	$arResult['LINK_TO_DOC'] = (in_array(LANGUAGE_ID, Array("ru", "kz", "ua", "by"))? 'https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=48&CHAPTER_ID=02699': 'https://www.bitrixsoft.com/support/training/course/index.php?COURSE_ID=26&LESSON_ID=6734');
}

if(in_array(LANGUAGE_ID, array("ru", "kz", "ua", "by")))
	$arResult['LINK_TO_REST_DOC'] = 'https://www.bitrix24.ru/apps/webhooks.php';
else
	$arResult['LINK_TO_REST_DOC'] = '';


$arResult['SIP_TYPE'] = $arParams['TYPE'] == CVoxImplantSip::TYPE_CLOUD ? CVoxImplantSip::TYPE_CLOUD : CVoxImplantSip::TYPE_OFFICE;
$arResult['LIST_SIP_NUMBERS'] = Array();
$res = Bitrix\Voximplant\ConfigTable::getList(Array(
	'select' => Array('ID', 'SEARCH_ID', 'PHONE_NAME'),
	'filter' => Array(
		'=PORTAL_MODE' => CVoxImplantConfig::MODE_SIP,
		'=SIP_CONFIG.TYPE' => $arResult['SIP_TYPE']
	)
));
while ($row = $res->fetch())
{
	if (strlen($row['PHONE_NAME']) <= 0)
	{
		$row['PHONE_NAME'] = substr($row['SEARCH_ID'], 0, 3) == 'reg'? GetMessage('VI_CONFIG_SIP_CLOUD_TITLE'): GetMessage('VI_CONFIG_SIP_OFFICE_TITLE');
		$row['PHONE_NAME'] = str_replace('#ID#', $row['ID'], $row['PHONE_NAME']);
	}
	$arResult['LIST_SIP_NUMBERS'][] = $row;
}

$arResult['IFRAME'] = $_REQUEST['IFRAME'] === 'Y';

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>