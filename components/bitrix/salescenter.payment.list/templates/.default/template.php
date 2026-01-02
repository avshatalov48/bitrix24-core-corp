<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @global \CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 */

use Bitrix\Main;
use Bitrix\Main\Loader;
use bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar\Facade\Toolbar;

Loader::includeModule('ui');

Main\UI\Extension::load([
	'salescenter.manager',
	'ui.notification',
	'ui.hint',
]);

global $APPLICATION;

if (!$arResult['hideSendButton'])
{
	$lock = (
		!$arResult['isSitePublished']
		|| !$arResult['isOrderPublicUrlAvailable']
		|| $arResult['disableSendButton']
		|| $arResult['isPaymentsLimitReached']
	);

	$buttonConfig = [
		'text' => Loc::getMessage('SPL_TEMPLETE_SALESCENTER_SEND_PAYMENT'),
		'click' => new Buttons\JsCode("BX.Salescenter.Payments.sendGridPayments();"),
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

$APPLICATION->IncludeComponent(
	'bitrix:crm.order.payment.list',
	'',
	[
		'INTERNAL_FILTER' => ['ORDER_ID' => $arResult['orderList']],
		'ENABLE_TOOLBAR' => false,
		'PATH_TO_ORDER_PAYMENT_LIST' => '/bitrix/components/bitrix/crm.order.payment.list/class.php?&site='.$this->arResult['SITE_ID'].'&'.bitrix_sessid_get(),
		'SHOW_ROW_CHECKBOXES' => true,
		'GRID_ID_SUFFIX' => $arResult['grid']['id'],
		'SALESCENTER_MODE' => true,
		'AJAX_LOADER' => [
			'method' => 'POST',
			'dataType' => 'ajax',
			'data' => [
				'PARAMS' => [
					'sessionId' => $arParams['sessionId'],
					'ownerId' => $arParams['ownerId'],
					'ownerTypeId' => $arParams['ownerTypeId'],
					'disableSendButton' => $arParams['disableSendButton'],
					'context' => $arParams['context'],
				],
			],
		],
	]
);

Toolbar::setFilter('');

$initJsData = [
	'gridId' => $arResult['grid']['fullId'],
	'context' => $arResult['context'],
	'sessionId' => $arParams['sessionId'],
	'isPaymentsLimitReached' => $arResult['isPaymentsLimitReached'],
];

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
			BX.Salescenter.Payments.init(<?=\CUtil::PhpToJSObject($initJsData);?>);
		});
	</script>
<?php

if ($arResult['isPaymentsLimitReached'] === true)
{
	CBitrix24::initLicenseInfoPopupJS('salescenterPaymentsLimit');
}

$APPLICATION->SetTitle(Loc::getMessage('SPL_TEMPLETE_PAYMENT_TITLE'));
