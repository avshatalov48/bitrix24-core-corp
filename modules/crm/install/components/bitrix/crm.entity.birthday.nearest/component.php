<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
use Bitrix\Crm\BirthdayReminder;
if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userID = CCrmSecurityHelper::GetCurrentUserID();
$isAdminUser = CCrmPerms::IsAdmin($userID);
$userPermissions = CCrmPerms::GetUserPermissions($userID);

$canReadLead = CCrmLead::CheckReadPermission(0, $userPermissions);
$canReadContact = CCrmContact::CheckReadPermission(0, $userPermissions);
if (!$canReadLead && !$canReadContact)
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['USER_ID'] = $userID;

$listID = isset($arParams['LIST_ID']) ? $arParams['LIST_ID'] : '';
if($listID === '')
{
	$listID = uniqid('birthdays_');
}
$arResult['LIST_ID'] = $listID;
$arResult['ERRORS'] = array();
$arResult['MESSAGES'] = array();

$arParams['NAME_FORMAT'] = $arResult['NAME_FORMAT'] = isset($arParams['NAME_FORMAT']) ? $arParams['NAME_FORMAT'] : '';

$utils = new CComponentUtil();

$arParams['DATE_FORMAT'] = isset($arParams['DATE_FORMAT']) ? $arParams['DATE_FORMAT'] : '';
$arResult['DATE_FORMAT'] = $arParams['DATE_FORMAT'] !== ''
	? $arParams['DATE_FORMAT'] : $utils->GetDateFormatDefault(true);

$arParams['INTERVAL_IN_DAYS'] = isset($arParams['INTERVAL_IN_DAYS']) ? (int)$arParams['INTERVAL_IN_DAYS'] : 7;
$intervalInDays = $arParams['INTERVAL_IN_DAYS'] > 0 ? $arParams['INTERVAL_IN_DAYS'] : 7;

$arParams['CHECK_PERMISSIONS'] = isset($arParams['CHECK_PERMISSIONS']) ? strtoupper($arParams['CHECK_PERMISSIONS']) : 'Y';
$enablePermissionCheck = $arParams['CHECK_PERMISSIONS'] !== 'N';

$arParams['LIMIT'] = isset($arParams['LIMIT']) ? (int)$arParams['LIMIT'] : 5;
$limit = $arParams['LIMIT'] > 0 ? $arParams['LIMIT'] : 5;

$currentDate = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'SHORT', SITE_ID);

$items = array();
if($canReadLead)
{
	$items = array_merge(
		BirthdayReminder::getNearestEntities(
			\CCrmOwnerType::Lead,
			$currentDate,
			'',
			$userID,
			$intervalInDays,
			$enablePermissionCheck,
			$limit
		),
		$items
	);
}
if($canReadContact)
{
	$items = array_merge(
		BirthdayReminder::getNearestEntities(
			\CCrmOwnerType::Contact,
			$currentDate,
			'',
			$userID,
			$intervalInDays,
			$enablePermissionCheck,
			$limit
		),
		$items
	);
}
sortByColumn($items, 'BIRTHDAY_SORT');

if(count($items) > $limit)
{
	$items = array_slice($items, 0, $limit);
}
foreach($items as &$item)
{
	$item['SHOW_URL'] = \CCrmOwnerType::GetEntityShowPath(
		$item['ENTITY_TYPE_ID'],
		$item['ID'],
		$enablePermissionCheck
	);
}
unset($item);

if(!empty($items))
{
	$arResult['ITEMS'] = $items;
	$this->IncludeComponentTemplate();
}