<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arParams['ID'] = preg_replace('/[^a-zA-Z_0-9]*/', '', $arParams['ID']);

if (strlen($arParams['ID']) <= 0)
	return;

$arParams['ALLOW_CLOSE'] = $arParams['ALLOW_CLOSE'] == 'N' ? 'N' : 'Y';

// additional parameters for template:
/*
'ICON' => css class for banner icon
'ICON_HREF' => href for an icon. useless without 'ICON' param
'CONTENT' => banner HTML content
*/

if ($arParams['ALLOW_CLOSE'] == 'Y')
{
	//user options
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/init_admin.php");

	// get closed status from user options
	if (class_exists('CUserOptions'))
	{
		$arHide = CUserOptions::GetOption(
			'intranet_bnr',
			'hide_banner',
			array($arParams['ID'] => false)
		);
		
		if ($arHide[$arParams['ID']])
			return false;
	}
	
	$APPLICATION->AddHeadScript('/bitrix/js/main/admin_tools.js');
}

$this->IncludeComponentTemplate();
?>