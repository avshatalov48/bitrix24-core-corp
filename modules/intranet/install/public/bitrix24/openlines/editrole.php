<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/intranet/public_bitrix24/openlines/editrole.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_after.php');

$APPLICATION->SetTitle(GetMessage('OL_PAGE_EDIT_ROLE_TITLE'));
?>
<?$APPLICATION->IncludeComponent(
	'bitrix:imopenlines.menu.top',
	'',
	[
		'COMPONENT_BASE_DIR' => '/openlines/',
	],
	false
);?>
<?$APPLICATION->IncludeComponent('bitrix:ui.sidepanel.wrapper',
								 '',
								 [
									 'POPUP_COMPONENT_NAME' => 'bitrix:imopenlines.settings.perms.role.edit',
									 'POPUP_COMPONENT_TEMPLATE_NAME' => ''
								 ]
);?>

<?require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');?>
