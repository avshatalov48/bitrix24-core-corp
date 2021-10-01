<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\UI\Extension::load(['salescenter.manager', 'ui.hint']);

$this->SetViewTarget('inside_pagetitle', 10000);
?>
<button class="ui-btn ui-btn-md ui-btn-light-border <?=(!$arResult['isSitePublished'] || !$arResult['isOrderPublicUrlAvailable'] || $arResult['disableSendButton'] || $arResult['isPaymentsLimitReached']) ? ' ui-btn-disabled' : ''?>" onclick="BX.Salescenter.Orders.sendGridOrders();"><?=\Bitrix\Main\Localization\Loc::getMessage('SALESCENTER_SEND_ORDER');?></button>
<button class="ui-btn ui-btn-md ui-btn-primary" onclick="<?= $arResult['addOrderOnClick'] ?>"><?=\Bitrix\Main\Localization\Loc::getMessage('SALESCENTER_ADD_ORDER');?></button>
<?
$this->EndViewTarget();

/*$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.toolbar',
	'slider',
	array(
		'TOOLBAR_ID' => 'salescenter_orders_toolbar',
		'BUTTONS' => $arResult['toolbarButtons'],
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);*/

$APPLICATION->IncludeComponent(
	'bitrix:crm.order.list',
	'',
	array(
		'ORDER_COUNT' => '20',
		'PATH_TO_ORDER_LIST' => '/saleshub/orders/?sessionId='.intval($arResult['sessionId']),
		'PATH_TO_CURRENT_LIST' => '/saleshub/orders/?sessionId='.intval($arResult['sessionId']),
		'PATH_TO_ORDER_DETAILS' => '/saleshub/orders/order/?orderId=#order_id#',
		'PATH_TO_ORDER_SHOW' => '/saleshub/orders/order/?orderId=#ID#',
		'PATH_TO_ORDER_EDIT' => '/saleshub/orders/order/?orderId=#ID#',
		'NAME_TEMPLATE' => '',
		'EXTERNAL_FILTER' => $arResult['externalFilter'],
		'SALESCENTER_MODE' => true,
		'GRID_ID' => $arResult['gridId'],
	),
	$component
);
?>
<script>
	BX.ready(function()
	{
		<?='BX.message('.\CUtil::PhpToJSObject(\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__)).');'?>
		<?='BX.message('.\CUtil::PhpToJSObject($arResult['messages']).');'?>
		BX.Salescenter.Manager.init(<?=\CUtil::PhpToJSObject($arResult);?>);

		BX.Salescenter.Orders.init(<?=\CUtil::PhpToJSObject($arResult);?>);

		<?if($arResult['orderId'] > 0)
		{
			?>BX.Salescenter.Orders.highlightOrder(<?=intval($arResult['orderId']);?>);<?
		}?>
	});
</script>
<?php

if($arResult['isPaymentsLimitReached'] === true)
{
	CBitrix24::initLicenseInfoPopupJS('salescenterPaymentsLimit');
}
?>