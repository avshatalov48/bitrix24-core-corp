<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;

CUtil::InitJSCore(array("ajax"));

if(!isset($arParams['HIDE_CONTROL_PANEL']) || $arParams['HIDE_CONTROL_PANEL'] <> 'Y') 
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'TAX_LIST',
			'ACTIVE_ITEM_ID' => '',
			'PATH_TO_COMPANY_LIST' => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
			'PATH_TO_COMPANY_EDIT' => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
			'PATH_TO_CONTACT_LIST' => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
			'PATH_TO_CONTACT_EDIT' => isset($arResult['PATH_TO_CONTACT_EDIT']) ? $arResult['PATH_TO_CONTACT_EDIT'] : '',
			'PATH_TO_DEAL_LIST' => isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '',
			'PATH_TO_DEAL_EDIT' => isset($arResult['PATH_TO_DEAL_EDIT']) ? $arResult['PATH_TO_DEAL_EDIT'] : '',
			'PATH_TO_LEAD_LIST' => isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '',
			'PATH_TO_LEAD_EDIT' => isset($arResult['PATH_TO_LEAD_EDIT']) ? $arResult['PATH_TO_LEAD_EDIT'] : '',
			'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
			'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
			'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
			'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
			'PATH_TO_REPORT_LIST' => isset($arResult['PATH_TO_REPORT_LIST']) ? $arResult['PATH_TO_REPORT_LIST'] : '',
			'PATH_TO_DEAL_FUNNEL' => isset($arResult['PATH_TO_DEAL_FUNNEL']) ? $arResult['PATH_TO_DEAL_FUNNEL'] : '',
			'PATH_TO_EVENT_LIST' => isset($arResult['PATH_TO_EVENT_LIST']) ? $arResult['PATH_TO_EVENT_LIST'] : '',
			'PATH_TO_PRODUCT_LIST' => isset($arResult['PATH_TO_PRODUCT_LIST']) ? $arResult['PATH_TO_PRODUCT_LIST'] : ''
		),
		$component
	);
}
if(!isset($arParams['HIDE_TOOLBAR']) || $arParams['HIDE_TOOLBAR'] <> 'Y')
{
	$tbButtons = array(
		array(
			'TEXT' => GetMessage('CRM_CONFIGS_LINK_TEXT'),
			'TITLE' => GetMessage('CRM_CONFIGS_LINK_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arResult['PATH_TO_CONFIGS_INDEX'], array()),
			'ICON' => 'go-back'
		)
	);
	if (!empty($tbButtons))
	{
		$APPLICATION->IncludeComponent(
			'bitrix:main.interface.toolbar',
			'',
			array(
				'BUTTONS' => $tbButtons
			),
			$component,
			array(
				'HIDE_ICONS' => 'Y'
			)
		);
	}
}

?>
<div class="crm-detail-lead-wrap-wrap">
	<div class="crm-detail-lead-wrap">
		<div class="crm-detail-title">
			<div class="crm-instant-editor-fld-block crm-title-name-wrap">
				<span class="crm-detail-title-name"><span class="crm-instant-editor-fld crm-instant-editor-fld-input"><span class="crm-instant-editor-fld-text"><?= htmlspecialcharsbx(GetMessage('CRM_EXCH1C_CONNECT_SECTION_TITLE')) ?></span></span></span>
			</div>
		</div>
		<div class="crm-instant-editor-fld-block">
			<div class="crm-detail-comments-text" style="padding-left: 0; cursor: text;">
				<p><?= htmlspecialcharsbx(GetMessage('CRM_EXCH1C_CONNECT_COMMENT_P1')) ?></p>
				<p><?= htmlspecialcharsbx(GetMessage('CRM_EXCH1C_CONNECT_COMMENT_P2', array('#URL#' => $arResult['EXCH_1C_SCRIPT_URL']))) ?></p>
			</div>
		</div>
	</div>
</div>
<div class="crm-detail-lead-wrap-wrap">
	<div class="crm-detail-lead-wrap">
		<div class="crm-detail-title">
			<div class="crm-instant-editor-fld-block crm-title-name-wrap">
				<span class="crm-detail-title-name"><span class="crm-instant-editor-fld crm-instant-editor-fld-input"><span class="crm-instant-editor-fld-text"><?= htmlspecialcharsbx(GetMessage('CRM_EXCH1C_SYNCSERV_SECTION_TITLE')) ?></span></span></span>
			</div>
		</div>
		<div class="crm-instant-editor-fld-block">
			<div class="crm-detail-comments-text" style="padding-left: 0;">

				<p><span style="text-decoration: underline; cursor: pointer;" onclick="BX.SidePanel.Instance.open('<?=htmlspecialcharsbx($arResult['PATH_TO_EXCH1C_INVOICE'])?>');"><?= htmlspecialcharsbx(GetMessage('CRM_EXCH1C_INV_SETTINGS_LINK_TITLE')) ?></span></p>
				<p><span style="text-decoration: underline; cursor: pointer;" onclick="BX.SidePanel.Instance.open('<?=htmlspecialcharsbx($arResult['PATH_TO_EXCH1C_CATALOG'])?>');"><?= htmlspecialcharsbx(GetMessage('CRM_EXCH1C_CAT_SETTINGS_LINK_TITLE')) ?></span></p>
			</div>
		</div>
	</div>
</div>
<div>
	<form id="CRM_EXCH1C_ENABLE_FORM" method="POST" action="<?=POST_FORM_ACTION_URI?>">
		<input id="CRM_EXCH1C_ENABLE_CHECK" type="checkbox" name="CRM_EXCH1C_ENABLE"<?= ($arResult['CRM_EXCH1C_ENABLED'] === 'Y') ? ' checked="checked"' : '' ?> /><span><?= ' '.htmlspecialcharsbx(GetMessage('CRM_EXCH1C_ENABLED_TITLE')) ?></span>
	</form>
</div>
<script>
	BX.ready(function () {
		var form =BX('CRM_EXCH1C_ENABLE_FORM');
		var check = BX('CRM_EXCH1C_ENABLE_CHECK');
		var url = form.getAttribute("action");
		if (form && url && check)
		{
			BX.bind(check, 'click',
				function () {
					var checked = (this.checked) ? "Y" : "N";
					BX.ajax.post(url, {"sessid": "<?=CUtil::JSEscape(bitrix_sessid())?>", "CRM_EXCH1C_ENABLE": checked}, function () {});
				}
			);
		}
	});
</script>
<?
