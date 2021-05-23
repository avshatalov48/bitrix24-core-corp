<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
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
?>
<div class="crm-detail-lead-wrap-wrap">
	<div class="crm-detail-lead-wrap">
		<div class="crm-detail-title">
			<div class="crm-instant-editor-fld-block crm-title-name-wrap">
				<span class="crm-detail-title-name"><span class="crm-instant-editor-fld crm-instant-editor-fld-input"><span class="crm-instant-editor-fld-text"><?= htmlspecialcharsbx(GetMessage('CRM_EXCH1C_CONNECT_SECTION_TITLE')) ?></span></span></span>
			</div>
		</div>
		<div class="crm-instant-editor-fld-block">
			<div class="crm-detail-comments-text" style="padding-left: 0;">
				<p><?= htmlspecialcharsbx(GetMessage('CRM_EXCH1C_COMMENT_FREE_P1')) ?></p>
				<p><?= htmlspecialcharsbx(GetMessage('CRM_EXCH1C_COMMENT_FREE_P2')) ?><a href="http://www.1c-bitrix.ru/products/intranet/features/crm/1c.php"><?= htmlspecialcharsbx(GetMessage('CRM_EXCH1C_BITRIX24_LINK1')) ?></a>.</p>
				<p><?= htmlspecialcharsbx(GetMessage('CRM_EXCH1C_COMMENT_FREE_P3')) ?></p>
				<p><?= htmlspecialcharsbx(GetMessage('CRM_EXCH1C_COMMENT_FREE_P4')) ?><a href="/settings/license.php"><?= htmlspecialcharsbx(GetMessage('CRM_EXCH1C_BITRIX24_LINK2')) ?></a>.</p>
			</div>
		</div>
	</div>
</div>
<?
