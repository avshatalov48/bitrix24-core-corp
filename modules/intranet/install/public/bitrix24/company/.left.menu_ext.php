<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/company/.left.menu_ext.php");

$aMenuLinks = Array(
	Array(
		GetMessage("MENU_STRUCTURE"),
		"/company/vis_structure.php",
		Array("/company/structure.php"),
		Array("menu_item_id"=>"menu_structure"),
		""
	),
	Array(
		GetMessage("MENU_EMPLOYEE"),
		"/company/",
		Array(),
		Array("menu_item_id"=>"menu_employee"),
		""
	)
);

if (IsModuleInstalled('lists'))
{
	$listUrl = 'https://helpdesk.bitrix24.ru/open/5316091/';

	if (CModule::IncludeModule('ui'))
	{
		\Bitrix\Main\UI\Extension::load('ui.info-helper');

		$listUrl = 'javascript:BX.UI.InfoHelper.show("limit_office_records_management");';
	}

	if (
		!IsModuleInstalled('bitrix24')
		|| CModule::IncludeModule('lists') && CLists::isFeatureEnabled('lists')
	)
	{
		$listUrl = '/company/lists/';
	}

	$aMenuLinks[] = [
		GetMessage('MENU_LISTS'),
		$listUrl,
		[],
		['menu_item_id' => 'menu_lists'],
		'',
	];
}
