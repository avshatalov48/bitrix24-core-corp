<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityPopupComponent $component */

if($arResult['IFRAME'])
{
	$APPLICATION->RestartBuffer();
	\CJSCore::init();
	\Bitrix\Main\UI\Extension::load("ui.hint");
	?><!DOCTYPE html>
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LANGUAGE_ID ?>" lang="<?=LANGUAGE_ID ?>">
	<head>
		<script data-skip-moving="true">
			// Prevent loading page without header and footer
			if(window === window.top)
			{
				window.location = "<?=CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('IFRAME'))); ?>";
			}
		</script>

		<?
		//The fastest way to close Slider Loader.
		Bitrix\Main\Page\Asset::getInstance()->setJsToBody(true);
		Bitrix\Main\Page\Asset::getInstance()->addString('
			<script>
				BX.ready(function() {
					var slider = top.BX && top.BX.SidePanel && top.BX.SidePanel.Instance.getSliderByWindow(window);
					if(!slider)
					{
						return;
					}
					
					slider.closeLoader();
					slider.setCacheable(false);
					if(slider.setPrintable)
					{
						slider.setPrintable(true);
					}
				});
			</script>
		', false, \Bitrix\Main\Page\AssetLocation::AFTER_CSS);
		?>

		<?$APPLICATION->ShowHead();?>
		<title><?$APPLICATION->ShowTitle()?></title>
		<style>.crm-iframe-popup,
			.crm-iframe-popup.crm-form-page,
			.crm-iframe-popup.crm-detail-page{
				background: #eef2f4 !important;
				padding: 0 15px 21px 21px;
			}
		</style>
	</head>
	<body class="crm-iframe-popup crm-detail-page template-<?= SITE_TEMPLATE_ID ?> <? if(!$arResult['IFRAME_USE_SCROLL']):?>crm-iframe-popup-no-scroll<?endif ?> <? $APPLICATION->ShowProperty('BodyClass'); ?>" onload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeLoad');" onunload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeUnload');">

	<div class="crm-iframe-header">
		<div class="pagetitle-wrap">
			<div class="pagetitle-inner-container">
				<div class="pagetitle-menu" id="pagetitle-menu"><?
					$APPLICATION->ShowViewContent("pagetitle");
					$APPLICATION->ShowViewContent("inside_pagetitle");
				?></div>
				<div class="pagetitle">
					<div class="pagetitle-title-prefix"><?php
						$APPLICATION->ShowViewContent('crm_details_title_prefix');
					?></div>
					<span id="pagetitle_icon"></span>
					<span id="pagetitle" class="pagetitle-item"><?$APPLICATION->ShowTitle()?></span>
					<span id="pagetitle_btn_wrapper" class="pagetitile-button-container">
						<?if($arResult['ENABLE_TITLE_EDIT'] && $arResult['ENTITY_ID'] > 0)
						{
							?><span id="pagetitle_edit" class="pagetitle-edit-button"></span><?
						}
						?><span id="page_url_copy_btn" class="crm-page-link-btn"></span>
					</span>	<?
					?><div id="pagetitle_sub" class="pagetitle-sub"><?
						$APPLICATION->ShowViewContent('crm_details_legend');
					?></div>
				</div>
			</div>
		</div>
	</div>

	<div class="crm-iframe-workarea" id="crm-content-outer">
	<div class="crm-iframe-sidebar"><?$APPLICATION->ShowViewContent("sidebar"); ?></div>
	<div class="crm-iframe-content"><?
}
else
{
	$controlPanelID = '';
	if($arResult['ENTITY_TYPE_ID'] === CCrmOwnerType::Lead)
	{
		$controlPanelID = 'LEAD';
	}
	elseif($arResult['ENTITY_TYPE_ID'] === CCrmOwnerType::Contact)
	{
		$controlPanelID = CCrmComponentHelper::getMenuActiveItemId(
			CCrmOwnerType::ContactName,
			$arResult['EXTRAS']['CATEGORY_ID']
		);
	}
	elseif($arResult['ENTITY_TYPE_ID'] === CCrmOwnerType::Company)
	{
		$controlPanelID = CCrmComponentHelper::getMenuActiveItemId(
			CCrmOwnerType::CompanyName,
			$arResult['EXTRAS']['CATEGORY_ID']
		);
	}
	elseif($arResult['ENTITY_TYPE_ID'] === CCrmOwnerType::Deal)
	{
		$controlPanelID = 'DEAL';
	}
	elseif($arResult['ENTITY_TYPE_ID'] === CCrmOwnerType::Order)
	{
		$controlPanelID = 'ORDER';
	}

	if (empty($arParams['DISABLE_TOP_MENU']) || $arParams['DISABLE_TOP_MENU'] != 'Y')
	{
		$APPLICATION->IncludeComponent(
			'bitrix:crm.control_panel',
			'',
			array(
				'ID' => $controlPanelID,
				'ACTIVE_ITEM_ID' => $controlPanelID,
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
}


if($arResult['ENTITY_TYPE_ID'] === CCrmOwnerType::Lead)
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.lead.details',
		'',
		[
			'ENTITY_ID' => $arResult['ENTITY_ID'],
			'EXTRAS' => $arResult['EXTRAS'] ?? [],
		]
	);
}
elseif($arResult['ENTITY_TYPE_ID'] === CCrmOwnerType::Contact)
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.contact.details',
		'',
		[
			'ENTITY_ID' => $arResult['ENTITY_ID'],
			'EXTRAS' => $arResult['EXTRAS'] ?? [],
		]
	);
}
elseif($arResult['ENTITY_TYPE_ID'] === CCrmOwnerType::Company)
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.company.details',
		'',
		[
			'ENTITY_ID' => $arResult['ENTITY_ID'],
			'EXTRAS' => $arResult['EXTRAS'] ?? [],
		]
	);
}
elseif($arResult['ENTITY_TYPE_ID'] === CCrmOwnerType::Deal)
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.deal.details',
		'',
		[
			'ENTITY_ID' => $arResult['ENTITY_ID'],
			'EXTRAS' => $arResult['EXTRAS'] ?? [],
		]
	);
}
elseif($arResult['ENTITY_TYPE_ID'] === CCrmOwnerType::Order)
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.order.details',
		'',
		[
			'ENTITY_ID' => $arResult['ENTITY_ID'],
			'EXTRAS' => $arResult['EXTRAS'] ?? [],
		]
	);
}
elseif($arResult['ENTITY_TYPE_ID'] === CCrmOwnerType::OrderCheck)
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.order.check.details',
		'',
		[
			'ENTITY_ID' => $arResult['ENTITY_ID'],
			'EXTRAS' => $arResult['EXTRAS'] ?? [],
		]
	);
}
elseif($arResult['ENTITY_TYPE_ID'] === CCrmOwnerType::CheckCorrection)
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.check.correction.details',
		'',
		[
			'ENTITY_ID' => $arResult['ENTITY_ID'],
			'EXTRAS' => $arResult['EXTRAS'] ?? [],
		]
	);
}
elseif($arResult['ENTITY_TYPE_ID'] === CCrmOwnerType::OrderShipment)
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.order.shipment.details',
		'',
		[
			'ENTITY_ID' => $arResult['ENTITY_ID'],
			'EXTRAS' => $arResult['EXTRAS'] ?? [],
		]
	);
}
elseif($arResult['ENTITY_TYPE_ID'] === CCrmOwnerType::OrderPayment)
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.order.payment.details',
		'',
		[
			'ENTITY_ID' => $arResult['ENTITY_ID'],
			'EXTRAS' => $arResult['EXTRAS'] ?? [],
		]
	);
}
else
{
	ShowError(
		GetMessage(
			'CRM_ENT_DETAIL_FRAME_COMPONENT_NOT_DEFINED',
			array('#TYPE_NAME#' => CCrmOwnerType::GetDescription($arResult['ENTITY_TYPE_ID']))
		)
	);
}

if ($arResult['IFRAME'])
{
	(new \Bitrix\Main\Event('crm', 'OnCrmEntityDetailsFrameBelowPage'))->send();
	?></div>
		</div>
		</body>
	</html><?
	require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
	die();
}
