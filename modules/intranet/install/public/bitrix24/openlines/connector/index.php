<?require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_after.php');?>
<?$APPLICATION->IncludeComponent(
	'bitrix:imopenlines.menu.top',
	'',
	[
		'COMPONENT_BASE_DIR' => '/openlines/',
	],
	false
);?>
<?$APPLICATION->IncludeComponent('bitrix:intranet.popup.provider',
								 '',
								 array(
								 		'COMPONENT_NAME' => 'bitrix:imconnector.connector.settings',
										'COMPONENT_TEMPLATE' => ''));?>

<?require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');?>
