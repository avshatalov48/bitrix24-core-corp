<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (!CModule::IncludeModule('voximplant'))
	return;

$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE, \Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
	return;

$viAccount = new CVoxImplantAccount();
if ($viAccount->GetAccountLang() === 'ua')
	return;

$arResult = Array();

$arResult['LIST_RENT_NUMBERS'] = Array();
$res = Bitrix\Voximplant\ConfigTable::getList([
	'select' => [
		'ID', 'PORTAL_MODE', 'PHONE_NAME', 'PHONE_NUMBER' => 'NUMBER.NUMBER',
	],
	'filter' => [
		'=PORTAL_MODE' => [CVoxImplantConfig::MODE_RENT, CVoxImplantConfig::MODE_GROUP]
	]
]);
while ($row = $res->fetch())
{
	switch ($row['PORTAL_MODE'])
	{
		case CVoxImplantConfig::MODE_GROUP:
			$name = $row['PHONE_NAME'];
			break;
		case CVoxImplantConfig::MODE_RENT:
			$name = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($row['PHONE_NUMBER'])->format(\Bitrix\Main\PhoneNumber\Format::INTERNATIONAL);
			break;
	}
	$arResult['LIST_RENT_NUMBERS'][$row['ID']] = Array(
		'PHONE_NAME' => htmlspecialcharsbx($name),
		'PORTAL_MODE' => $row['PORTAL_MODE']
	);
}

$arResult['ACCOUNT_NAME'] = str_replace('.bitrixphone.com', '', $viAccount->GetAccountName());
$arResult['ACCOUNT_LANG'] = $viAccount->GetAccountLang();
$arResult['ORDER_STATUS'] = CVoxImplantPhoneOrder::GetStatus(true);

$arResult['IFRAME'] = $_REQUEST['IFRAME'] === 'Y';

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;
?>