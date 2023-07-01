<?

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $APPLICATION;
$menuItems = $APPLICATION->IncludeComponent('bitrix:lists.menu', '', [
	'IBLOCK_TYPE_ID' => 'lists',
	'IS_SEF' => 'Y',
	'SEF_BASE_URL' => SITE_DIR . 'company/lists/',
	'SEF_LIST_BASE_URL' => '#list_id#/',
	'SEF_LIST_URL' => '#list_id#/view/#section_id#/',
	'CACHE_TYPE' => 'A',
	'CACHE_TIME' => '36000000',
],
	false,
	[
		'HIDE_ICONS' => 'N',
	]
);

if (count($menuItems) > 0)
{
	Loc::loadMessages(
		$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/company/lists/.sub.menu_ext.php'
	);

	$menuItems[] = [
		Loc::getMessage('LISTS_INDEX_TITLE'),
		SITE_DIR . 'company/lists/',
		[],
		[],
		''
	];
}

$aMenuLinks = $menuItems;