<?php

$siteID = isset($_REQUEST['site'])? mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site']), 0, 2) : '';
if($siteID !== '')
{
	define('SITE_ID', $siteID);
}

use Bitrix\Crm;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

Loc::loadMessages(__FILE__);

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityPopupComponent $component */

$APPLICATION->RestartBuffer();
?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= LANGUAGE_ID ?>" lang="<?= LANGUAGE_ID ?>">
<head>
	<script>
		// Prevent loading page without header and footer
		if(window === window.top)
		{
			window.location = "<?=CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('IFRAME'))); ?>";
		}
	</script>
	<?php
	$APPLICATION->ShowHead();
	?>
	<style>.task-iframe-popup,
		.task-iframe-popup.task-form-page,
		.task-iframe-popup.task-detail-page{
			background: #eef2f4 !important;
			padding: 0 15px 21px 21px;
		}</style>
</head>
<body class="crm-iframe-popup crm-detail-page template-<?= SITE_TEMPLATE_ID ?> crm-iframe-popup-no-scroll crm-order-payment-voucher-wrapper <?php $APPLICATION->ShowProperty('BodyClass'); ?>" onload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeLoad');" onunload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeUnload');">

<div class="crm-iframe-header">
	<div class="pagetitle-wrap">
		<div class="pagetitle-inner-container">
			<div class="pagetitle">
				<span id="pagetitle" class="pagetitle-item"><?php
					$APPLICATION->ShowTitle()
				?></span>
			</div>
		</div>
	</div>
</div>

<div class="crm-iframe-workarea" id="crm-content-outer">
	<div class="crm-iframe-sidebar"><?php
		$APPLICATION->ShowViewContent("sidebar");
	?></div>
	<div class="crm-iframe-content"><?php

if (!Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
}
elseif (!Loader::includeModule('sale'))
{
	ShowError(GetMessage('SALE_MODULE_NOT_INSTALLED'));
}
else
{
	$orderId = isset($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : 0;

	$accessAllow = false;
	if (
		check_bitrix_sessid()
		&& Crm\Security\EntityAuthorization::IsAuthorized()
	)
	{
		$serviceContainer = Crm\Service\Container::getInstance();
		if ($orderId > 0)
		{
			$accessAllow = $serviceContainer
				->getUserPermissions()
				->item()
				->canUpdate(\CCrmOwnerType::Order, $orderId)
			;
		}
		else
		{
			$accessAllow = $serviceContainer
				->getUserPermissions()
				->entityType()
				->canAddItems(\CCrmOwnerType::Order)
			;
		}
	}
	if (!$accessAllow)
	{
		ShowError(GetMessage('CRM_ACCESS_DENIED'));
	}
	else
	{
		$componentParams['ENTITY_ID'] = $orderId;
		$componentParams['BASKET_ID'] = $_REQUEST['basket_id'] ?? 0;
		$componentParams['ORDER_ID'] = $_REQUEST['order_id'] ?? 0;
		$componentParams['PRODUCT_ID'] = (int)($_REQUEST['product_id'] ?? 0);
		$componentParams['CURRENCY'] = (string)($_REQUEST['currency'] ?? \CCrmCurrency::GetBaseCurrencyID());
		$componentParams['ENTITY_TYPE'] = CCrmOwnerType::Order;
		$componentParams['POPUP_MODE'] = 'Y';
		$componentParams['EXTERNAL_CONTEXT_ID'] = (string)($_REQUEST['external_context_id'] ?? '');

		$APPLICATION->IncludeComponent(
			'bitrix:crm.order.product.details',
			'',
			$componentParams,
			false
		);
	}
}

?></div>
</div>
</body>
</html><?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
