<?
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/intranet/public_bitrix24/crm/tunnels/index.php');
$APPLICATION->SetTitle(GetMessage('TITLE'));

if (CModule::IncludeModule('crm'))
{
	if ($_REQUEST['IFRAME'] == 'Y' && $_REQUEST['IFRAME_TYPE'] == 'SIDE_SLIDER')
	{
		$APPLICATION->IncludeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			array(
				'POPUP_COMPONENT_NAME' => 'bitrix:crm.sales.tunnels',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => []
			)
		);
	}
	else
	{
		$APPLICATION->IncludeComponent(
			'bitrix:crm.sales.tunnels',
			'',
			[]
		);
	}

}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
?>
