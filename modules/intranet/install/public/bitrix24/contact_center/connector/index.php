<?php
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
global $APPLICATION;

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/contact_center/index.php');
$APPLICATION->SetTitle(Loc::getMessage('TITLE'));


$showConnector = Context::getCurrent()->getRequest()->get('showConnectorInNewSlider') !== 'Y'
	&& Context::getCurrent()->getRequest()->get('IFRAME') === 'Y'
;

if (!$showConnector)
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

	$url = CUtil::JSescape((new \Bitrix\Main\Web\Uri(Context::getCurrent()->getServer()->getRequestUri()))
		->deleteParams(['showConnectorInNewSlider'])
		->getUri())
	;
	$width = 700;
	if(
		!empty($_REQUEST['ID'])
		&& $_REQUEST['ID'] === 'facebook'
	)
	{
		$width = 1000;
	}
	$APPLICATION->addViewContent('below_pagetitle', <<<HTML
<script>
	BX.ready(function () {
		BX.SidePanel.Instance.open(
			'{$url}',
			{
				cacheable: false,
				allowChangeHistory: true,
				width: {$width},
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
HTML
	);

	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:intranet.contact_center.list',
			"USE_PADDING" => false,
		]
	);

}
else
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

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');