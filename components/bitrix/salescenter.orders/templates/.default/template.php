<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\UI\Extension::load(['salescenter.manager', 'ui.hint']);

$this->SetViewTarget('inside_pagetitle', 10000);
?>
<?php if (!$arResult['hideSendButton']): ?>
<button class="ui-btn ui-btn-md ui-btn-light-border <?=(!$arResult['isSitePublished'] || !$arResult['isOrderPublicUrlAvailable'] || $arResult['disableSendButton'] || $arResult['isPaymentsLimitReached']) ? ' ui-btn-disabled' : ''?>" onclick="BX.Salescenter.Orders.sendGridOrders();"><?=\Bitrix\Main\Localization\Loc::getMessage('SALESCENTER_SEND_ORDER');?></button>
<?php endif; ?>
<button class="ui-btn ui-btn-md ui-btn-primary" onclick="<?= $arResult['addOrderOnClick'] ?>"><?=\Bitrix\Main\Localization\Loc::getMessage('SALESCENTER_ADD_ORDER');?></button>
<?
$this->EndViewTarget();

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
		<?php if ($arResult['context'] === 'sms'): ?>
		BX.hide(document.getElementById('send_to_chat'));
		<?php endif; ?>
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