<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/crm-entity-show.css');
$APPLICATION->AddHeadScript('/bitrix/js/crm/common.js');
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'PERMS_EDIT',
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

if($arResult['NEED_FOR_REBUILD_COMPANY_ATTRS']):
	?><div id="rebuildCompanyAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_CONFIG_PERMS_REBUILD_COMPANY_ATTRS', array('#ID#' => 'rebuildCompanyAttrsLink', '#URL#' => '#'))?>
	</div><?
endif;

if($arResult['NEED_FOR_REBUILD_CONTACT_ATTRS']):
	?><div id="rebuildContactAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_CONFIG_PERMS_REBUILD_CONTACT_ATTRS', array('#ID#' => 'rebuildContactAttrsLink', '#URL#' => '#'))?>
	</div><?
endif;

if($arResult['NEED_FOR_REBUILD_DEAL_ATTRS']):
	?><div id="rebuildDealAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_CONFIG_PERMS_REBUILD_DEAL_ATTRS', array('#ID#' => 'rebuildDealAttrsLink', '#URL#' => '#'))?>
	</div><?
endif;

if($arResult['NEED_FOR_REBUILD_LEAD_ATTRS']):
	?><div id="rebuildLeadAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_CONFIG_PERMS_REBUILD_LEAD_ATTRS', array('#ID#' => 'rebuildLeadAttrsLink', '#URL#' => '#'))?>
	</div><?
endif;

if($arResult['NEED_FOR_REBUILD_QUOTE_ATTRS']):
	?><div id="rebuildQuoteAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_CONFIG_PERMS_REBUILD_QUOTE_ATTRS', array('#ID#' => 'rebuildQuoteAttrsLink', '#URL#' => '#'))?>
	</div><?
endif;

if($arResult['NEED_FOR_REBUILD_INVOICE_ATTRS']):
	?><div id="rebuildInvoiceAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_CONFIG_PERMS_REBUILD_INVOICE_ATTRS', array('#ID#' => 'rebuildInvoiceAttrsLink', '#URL#' => '#'))?>
	</div><?
endif;

$APPLICATION->IncludeComponent(
	'bitrix:crm.config.perms.role.edit',
	'',
	Array(
		'ROLE_ID' => $arResult['VARIABLES']['role_id'],
		'PATH_TO_ROLE_EDIT' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['role_edit'],
		'PATH_TO_ENTITY_LIST' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['entity_list']
	),
	$component
);

if($arResult['NEED_FOR_REBUILD_COMPANY_ATTRS']
	|| $arResult['NEED_FOR_REBUILD_CONTACT_ATTRS']
	|| $arResult['NEED_FOR_REBUILD_DEAL_ATTRS']
	|| $arResult['NEED_FOR_REBUILD_LEAD_ATTRS']
	|| $arResult['NEED_FOR_REBUILD_QUOTE_ATTRS']
	|| $arResult['NEED_FOR_REBUILD_INVOICE_ATTRS']):
?><script type="text/javascript">
BX.ready(
	function()
	{
		BX.CrmEntityAccessManager.messages =
		{
			rebuildCompanyAccessAttrsDlgTitle: "<?=GetMessageJS('CRM_CONFIG_PERMS_REBUILD_COMPANY_ATTR_DLG_TITLE')?>",
			rebuildCompanyAccessAttrsDlgSummary: "<?=GetMessageJS('CRM_CONFIG_PERMS_REBUILD_COMPANY_ATTR_DLG_SUMMARY')?>",
			rebuildContactAccessAttrsDlgTitle: "<?=GetMessageJS('CRM_CONFIG_PERMS_REBUILD_CONTACT_ATTR_DLG_TITLE')?>",
			rebuildContactAccessAttrsDlgSummary: "<?=GetMessageJS('CRM_CONFIG_PERMS_REBUILD_CONTACT_ATTR_DLG_SUMMARY')?>",
			rebuildDealAccessAttrsDlgTitle: "<?=GetMessageJS('CRM_CONFIG_PERMS_REBUILD_DEAL_ATTR_DLG_TITLE')?>",
			rebuildDealAccessAttrsDlgSummary: "<?=GetMessageJS('CRM_CONFIG_PERMS_REBUILD_DEAL_ATTR_DLG_SUMMARY')?>",
			rebuildLeadAccessAttrsDlgTitle: "<?=GetMessageJS('CRM_CONFIG_PERMS_REBUILD_LEAD_ATTR_DLG_TITLE')?>",
			rebuildLeadAccessAttrsDlgSummary: "<?=GetMessageJS('CRM_CONFIG_PERMS_REBUILD_LEAD_ATTR_DLG_SUMMARY')?>",
			rebuildQuoteAccessAttrsDlgTitle: "<?=GetMessageJS('CRM_CONFIG_PERMS_REBUILD_QUOTE_ATTR_DLG_TITLE')?>",
			rebuildQuoteAccessAttrsDlgSummary: "<?=GetMessageJS('CRM_CONFIG_PERMS_REBUILD_QUOTE_ATTR_DLG_SUMMARY')?>",
			rebuildInvoiceAccessAttrsDlgTitle: "<?=GetMessageJS('CRM_CONFIG_PERMS_REBUILD_INVOICE_ATTR_DLG_TITLE')?>",
			rebuildInvoiceAccessAttrsDlgSummary: "<?=GetMessageJS('CRM_CONFIG_PERMS_REBUILD_INVOICE_ATTR_DLG_SUMMARY')?>"
		};
		BX.CrmLongRunningProcessDialog.messages =
		{
			startButton: "<?=GetMessageJS('CRM_CONFIG_PERMS_LRP_DLG_BTN_START')?>",
			stopButton: "<?=GetMessageJS('CRM_CONFIG_PERMS_LRP_DLG_BTN_STOP')?>",
			closeButton: "<?=GetMessageJS('CRM_CONFIG_PERMS_LRP_DLG_BTN_CLOSE')?>",
			wait: "<?=GetMessageJS('CRM_CONFIG_PERMS_LRP_DLG_WAIT')?>",
			requestError: "<?=GetMessageJS('CRM_CONFIG_PERMS_LRP_DLG_REQUEST_ERR')?>"
		};

		var mgr = BX.CrmEntityAccessManager.create("mgr", { serviceUrl: "<?=SITE_DIR?>bitrix/components/bitrix/crm.config.perms/ajax.php?&<?=bitrix_sessid_get()?>" });
		//COMPANY
		BX.addCustomEvent(
			mgr,
			"ON_COMPANY_ATTRS_REBUILD_COMPLETE",
			function()
			{
				var msg = BX("rebuildCompanyAttrsMsg");
				if(msg)
				{
					msg.style.display = "none";
				}
			}
		);
		var companyLink = BX("rebuildCompanyAttrsLink");
		if(companyLink)
		{
			BX.bind(
				companyLink,
				"click",
				function(e)
				{
					mgr.rebuildCompanyAttrs();
					return BX.PreventDefault(e);
				}
			);
		}
		//CONTACT
		BX.addCustomEvent(
			mgr,
			"ON_CONTACT_ATTRS_REBUILD_COMPLETE",
			function()
			{
				var msg = BX("rebuildContactAttrsMsg");
				if(msg)
				{
					msg.style.display = "none";
				}
			}
		);
		var contactLink = BX("rebuildContactAttrsLink");
		if(contactLink)
		{
			BX.bind(
				contactLink,
				"click",
				function(e)
				{
					mgr.rebuildContactAttrs();
					return BX.PreventDefault(e);
				}
			);
		}
		//DEAL
		BX.addCustomEvent(
			mgr,
			"ON_DEAL_ATTRS_REBUILD_COMPLETE",
			function()
			{
				var msg = BX("rebuildDealAttrsMsg");
				if(msg)
				{
					msg.style.display = "none";
				}
			}
		);
		var dealLink = BX("rebuildDealAttrsLink");
		if(dealLink)
		{
			BX.bind(
				dealLink,
				"click",
				function(e)
				{
					mgr.rebuildDealAttrs();
					return BX.PreventDefault(e);
				}
			);
		}
		//LEAD
		BX.addCustomEvent(
			mgr,
			"ON_LEAD_ATTRS_REBUILD_COMPLETE",
			function()
			{
				var msg = BX("rebuildLeadAttrsMsg");
				if(msg)
				{
					msg.style.display = "none";
				}
			}
		);
		var leadLink = BX("rebuildLeadAttrsLink");
		if(leadLink)
		{
			BX.bind(
				leadLink,
				"click",
				function(e)
				{
					mgr.rebuildLeadAttrs();
					return BX.PreventDefault(e);
				}
			);
		}
		//QUOTE
		BX.addCustomEvent(
			mgr,
			"ON_QUOTE_ATTRS_REBUILD_COMPLETE",
			function()
			{
				var msg = BX("rebuildQuoteAttrsMsg");
				if(msg)
				{
					msg.style.display = "none";
				}
			}
		);
		var quoteLink = BX("rebuildQuoteAttrsLink");
		if(quoteLink)
		{
			BX.bind(
				quoteLink,
				"click",
				function(e)
				{
					mgr.rebuildQuoteAttrs();
					return BX.PreventDefault(e);
				}
			);
		}
		//INVOICE
		BX.addCustomEvent(
			mgr,
			"ON_INVOICE_ATTRS_REBUILD_COMPLETE",
			function()
			{
				var msg = BX("rebuildInvoiceAttrsMsg");
				if(msg)
				{
					msg.style.display = "none";
				}
			}
		);
		var invoiceLink = BX("rebuildInvoiceAttrsLink");
		if(invoiceLink)
		{
			BX.bind(
				invoiceLink,
				"click",
				function(e)
				{
					mgr.rebuildInvoiceAttrs();
					return BX.PreventDefault(e);
				}
			);
		}
	}
);
</script><?
endif;