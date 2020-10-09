<?require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_after.php');?>
<?$APPLICATION->IncludeComponent(
	'bitrix:intranet.contact_center.menu.top',
	'',
	[
		'COMPONENT_BASE_DIR' => '/contact_center/',
	],
	false
);?>
<?$APPLICATION->IncludeComponent('bitrix:intranet.popup.provider',
								 '',
								 [
								 		'COMPONENT_NAME' => 'bitrix:imconnector.connector.settings',
										'COMPONENT_TEMPLATE' => ''
								 ]
);?>

<?require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');?>
