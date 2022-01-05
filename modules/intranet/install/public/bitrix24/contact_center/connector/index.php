<?
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
global $APPLICATION;
if($_GET['IFRAME'] !== 'Y')
{
	IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/contact_center/index.php');
	$APPLICATION->SetTitle(Loc::getMessage('TITLE'));
}?>
<?if($_GET['IFRAME'] !== 'Y')
{
	$APPLICATION->IncludeComponent(
		'bitrix:intranet.contact_center.menu.top',
		'',
		[
			'COMPONENT_BASE_DIR' => '/contact_center/',
			'SECTION_ACTIVE' => 'contact_center'
		],
		false
	);

	$APPLICATION->IncludeComponent(
		'bitrix:intranet.popup.provider',
		'',
		[
			'COMPONENT_NAME' => 'bitrix:intranet.contact_center.list',
			'COMPONENT_TEMPLATE_NAME' => '',
			'COMPONENT_POPUP_TEMPLATE_NAME' => 'contact_center',
			'COMPONENT_PARAMS' => []
		],
		false
	);?>
<?
	$width = 700;
	/*if(
		!empty($_REQUEST['ID'])
		&& $_REQUEST['ID'] === 'facebook'
	)
	{
		$width = 1000;
	}*/
?>
	<script>
		BX.ready(function () {
			BX.SidePanel.Instance.open(
				'<?=CUtil::JSescape(Context::getCurrent()->getServer()->getRequestUri())?>',
				{
					cacheable: false,
					allowChangeHistory: true,
					width: <?=$width?>,
					events: {
						onCloseComplete: function(event) {
							setTimeout(function() {
								window.history.replaceState({}, '', '/contact_center/');
							}, 500);
						}
					}
				}
			);
		});
	</script>
<?
} ?>
<?if($_GET['IFRAME'] === 'Y')
{
	$APPLICATION->IncludeComponent('bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:imconnector.connector.settings',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'USE_PADDING' => true,
			'PLAIN_VIEW' => true
		]
	);
}
?>
<?require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');?>
