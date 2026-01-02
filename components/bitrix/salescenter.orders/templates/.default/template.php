<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar\Facade\Toolbar;

/**
 * @global \CMain $APPLICATION
 * @var \SalesCenterOrderListComponent $component
 * @var array $arResult
 */

Loader::includeModule('ui');

Extension::load([
	'salescenter.manager',
	'ui.hint',
]);

if (!$arResult['hideSendButton'])
{
	$lock = (
		!$arResult['isSitePublished']
		|| !$arResult['isOrderPublicUrlAvailable']
		|| $arResult['disableSendButton']
		|| $arResult['isPaymentsLimitReached']
	);

	$buttonConfig = [
		'text' => Loc::getMessage('SALESCENTER_SEND_ORDER'),
		'click' => new Buttons\JsCode("BX.Salescenter.Orders.sendGridOrders();"),
		'dataset' => [
			'toolbar-collapsed-icon' => Buttons\Icon::INFO,
		],
	];
	$button = new Buttons\Button($buttonConfig);
	$button->addClass(Buttons\Color::LIGHT_BORDER);
	if ($lock)
	{
		$button->setDisabled(true);
	}
	Toolbar::addButton($button);
	unset($button);
}
Toolbar::addButton(new Buttons\AddButton([
	'text' => Loc::getMessage('SALESCENTER_ADD_ORDER'),
	'click' => new Buttons\JsCode(
		$arResult['addOrderOnClick']
	),
	'dataset' => [
		'toolbar-collapsed-icon' => Buttons\Icon::ADD,
	],
]));

$APPLICATION->IncludeComponent(
	'bitrix:crm.order.list',
	'',
	[
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
	],
	$component
);
Toolbar::setFilter('');

?>
<script>
	BX.ready(function()
	{
		<?php
		if ($arResult['context'] === 'sms'):
		?>
		BX.hide(document.getElementById('send_to_chat'));
		<?php
		endif;
		?>
		BX.message(<?= Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
		BX.message(<?= Json::encode($arResult['messages']) ?>);

		BX.Salescenter.Manager.init(<?=\CUtil::PhpToJSObject($arResult);?>);

		BX.Salescenter.Orders.init(<?=\CUtil::PhpToJSObject($arResult);?>);

		<?php
		// $arResult['orderId'] have type integer
		if ($arResult['orderId'] > 0)
		{
			?>BX.Salescenter.Orders.highlightOrder(<?= $arResult['orderId'] ;?>);<?php
		}
		?>
	});
</script>
<?php

if ($arResult['isPaymentsLimitReached'] === true)
{
	CBitrix24::initLicenseInfoPopupJS('salescenterPaymentsLimit');
}
