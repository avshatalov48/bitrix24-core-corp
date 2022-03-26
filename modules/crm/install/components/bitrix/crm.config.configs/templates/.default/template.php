<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');

if($arResult['ENABLE_CONTROL_PANEL'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'CONFIG',
			'ACTIVE_ITEM_ID' => '',
			'PATH_TO_COMPANY_LIST' => isset($arParams['PATH_TO_COMPANY_LIST']) ? $arParams['PATH_TO_COMPANY_LIST'] : '',
			'PATH_TO_COMPANY_EDIT' => isset($arParams['PATH_TO_COMPANY_EDIT']) ? $arParams['PATH_TO_COMPANY_EDIT'] : '',
			'PATH_TO_CONTACT_LIST' => isset($arParams['PATH_TO_CONTACT_LIST']) ? $arParams['PATH_TO_CONTACT_LIST'] : '',
			'PATH_TO_CONTACT_EDIT' => isset($arParams['PATH_TO_CONTACT_EDIT']) ? $arParams['PATH_TO_CONTACT_EDIT'] : '',
			'PATH_TO_DEAL_LIST' => isset($arParams['PATH_TO_DEAL_LIST']) ? $arParams['PATH_TO_DEAL_LIST'] : '',
			'PATH_TO_DEAL_EDIT' => isset($arParams['PATH_TO_DEAL_EDIT']) ? $arParams['PATH_TO_DEAL_EDIT'] : '',
			'PATH_TO_LEAD_LIST' => isset($arParams['PATH_TO_LEAD_LIST']) ? $arParams['PATH_TO_LEAD_LIST'] : '',
			'PATH_TO_LEAD_EDIT' => isset($arParams['PATH_TO_LEAD_EDIT']) ? $arParams['PATH_TO_LEAD_EDIT'] : '',
			'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
			'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
			'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
			'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
			'PATH_TO_REPORT_LIST' => isset($arParams['PATH_TO_REPORT_LIST']) ? $arParams['PATH_TO_REPORT_LIST'] : '',
			'PATH_TO_DEAL_FUNNEL' => isset($arParams['PATH_TO_DEAL_FUNNEL']) ? $arParams['PATH_TO_DEAL_FUNNEL'] : '',
			'PATH_TO_EVENT_LIST' => isset($arParams['PATH_TO_EVENT_LIST']) ? $arParams['PATH_TO_EVENT_LIST'] : '',
			'PATH_TO_PRODUCT_LIST' => isset($arParams['PATH_TO_PRODUCT_LIST']) ? $arParams['PATH_TO_PRODUCT_LIST'] : ''
		),
		$component
	);
}

$arTabs[] = array(
	'id' => 'tab_main',
	'name' => GetMessage('CRM_TAB_GENERAL'),
	'title' => GetMessage('CRM_TAB_GENERAL_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_main']
);

$arTabs[] = array(
	'id' => 'tab_rest',
	'name' => GetMessage('CRM_TAB_REST_2'),
	'title' => GetMessage('CRM_TAB_REST_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_rest']
);

$arTabs[] = array(
	'id' => 'tab_activity_config',
	'name' => GetMessage('CRM_TAB_ACTIVITY_CONFIG'),
	'title' => GetMessage('CRM_TAB_ACTIVITY_CONFIG_TITLE2'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_activity_config']
);

$arTabs[] = array(
	'id' => 'tab_history',
	'name' => GetMessage('CRM_TAB_HISTORY'),
	'title' => GetMessage('CRM_TAB_HISORY_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_history']
);

$arTabs[] = array(
	'id' => 'tab_livefeed',
	'name' => GetMessage('CRM_TAB_LIVEFEED2'),
	'title' => GetMessage('CRM_TAB_LIVEFEED_TITLE2'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_livefeed']
);

$arTabs[] = array(
	'id' => 'tab_status_config',
	'name' => GetMessage('CRM_TAB_STATUS_CONFIG'),
	'title' => GetMessage('CRM_TAB_STATUS_CONFIG_TITLE'),
	'fields' => $arResult['FIELDS']['tab_status_config']
);

$arTabs[] = array(
	'id' => 'tab_format',
	'name' => GetMessage('CRM_TAB_FORMAT'),
	'title' => GetMessage('CRM_TAB_FORMAT_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_format']
);

$arTabs[] = array(
	'id' => 'tab_dup_control',
	'name' => GetMessage('CRM_TAB_DUPLICATE_CONTROL'),
	'title' => GetMessage('CRM_TAB_DUPLICATE_CONTROL_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_dup_control']
);

$arTabs[] = array(
	'id' => 'tab_recycle_bin_config',
	'name' => GetMessage('CRM_TAB_RECYCLE_BIN_CONFIG'),
	'title' => GetMessage('CRM_TAB_RECYCLE_BIN_CONFIG_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_recycle_bin_config']
);

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'TABS' => $arTabs,
		'BUTTONS' => array(
			'standard_buttons' =>  true,
			'back_url' => $arResult['BACK_URL']
		),
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'N'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
if(SITE_TEMPLATE_ID === 'bitrix24'):
?><script type="text/javascript">
	BX.ready(
			function()
			{
				BX.CrmInterfaceFormUtil.disableThemeSelection("<?= CUtil::JSEscape($arResult["FORM_ID"])?>");
			}
	);
</script><?
endif;
?><script type="text/javascript">
	BX.ready(
			function()
			{
				var form = BX('form_<?=CUtil::JSEscape($arResult['FORM_ID'])?>');
				if(!form)
				{
					return;
				}

				var customFormatID = <?=CUtil::JSEscape(CCrmCallToUrl::Custom)?>;
				var formatSelector = BX.findChild(form, { 'tag': 'SELECT', 'attribute': { 'name': 'CALLTO_FORMAT' } }, true);
				BX.bind(
					formatSelector,
					'change',
					BX.delegate(
						function()
						{
							var show = formatSelector.value == customFormatID;
							BX.CrmInterfaceFormUtil.showFormRow(show, BX.findChild(form, { 'tag': 'INPUT', 'attribute': { 'name': 'CALLTO_URL_TEMPLATE' } }, true));
							BX.CrmInterfaceFormUtil.showFormRow(show, BX.findChild(form, { 'tag': 'TEXTAREA', 'attribute': { 'name': 'CALLTO_CLICK_HANDLER' } }, true));
						}
					)
				);

				if(formatSelector.value != customFormatID)
				{
					BX.CrmInterfaceFormUtil.showFormRow(false, BX.findChild(form, { 'tag': 'INPUT', 'attribute': { 'name': 'CALLTO_URL_TEMPLATE' } }, true));
					BX.CrmInterfaceFormUtil.showFormRow(false, BX.findChild(form, { 'tag': 'TEXTAREA', 'attribute': { 'name': 'CALLTO_CLICK_HANDLER' } }, true));
				}

				BX.AddressFormatSelector.create(
					"<?=CUtil::JSEscape($arResult['FORM_ID'])?>",
					{
						descrContainerId: <?=$arResult['ADDR_FORMAT_DESCR_ID']?>,
						controlPrefix: "<?=CUtil::JSEscape($arResult['ADDR_FORMAT_CONTROL_PREFIX'])?>",
						typeInfos: <?=CUtil::PhpToJSObject($arResult['ADDR_FORMAT_INFOS'])?>
					}
				);

				var nodeAutoGenRc = form.querySelector('input[type="checkbox"][name="AUTO_GEN_RC"]');
				var nodeAutoUsingFinishedLead = form.querySelector('input[type="checkbox"][name="AUTO_USING_FINISHED_LEAD"]');
				if (nodeAutoGenRc && nodeAutoUsingFinishedLead)
				{
					BX.bind(nodeAutoGenRc, 'change', function() {
						nodeAutoUsingFinishedLead.checked = false;
						BX.CrmInterfaceFormUtil.showFormRow(!nodeAutoGenRc.checked, nodeAutoUsingFinishedLead);
					});
				}

				var productPriceEditSetting = form.querySelector('input[type="checkbox"][name="ENABLE_ENTITY_CATALOG_PRICE_EDIT"]');
				var productPriceSaveSetting = form.querySelector('input[type="checkbox"][name="ENABLE_ENTITY_CATALOG_PRICE_SAVE"]');
				if (productPriceEditSetting && productPriceSaveSetting)
				{
					BX.bind(productPriceEditSetting, 'change', function() {
						BX.CrmInterfaceFormUtil.showFormRow(productPriceEditSetting.checked, productPriceSaveSetting);
					});
				}
			}
	);
</script>