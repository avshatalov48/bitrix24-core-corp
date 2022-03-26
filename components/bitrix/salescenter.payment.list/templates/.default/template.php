<?php

/**
 * @var array $arParams
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

Main\UI\Extension::load(['salescenter.manager', 'ui.notification', 'ui.hint']);

global $APPLICATION;

$this->SetViewTarget('inside_pagetitle', 10000);
?>
<?php if (!$arResult['hideSendButton']): ?>
	<button class="ui-btn ui-btn-md ui-btn-light-border <?=(!$arResult['isSitePublished'] || !$arResult['isOrderPublicUrlAvailable'] || $arResult['disableSendButton'] || $arResult['isPaymentsLimitReached']) ? ' ui-btn-disabled' : ''?>" onclick="BX.Salescenter.Payments.sendGridPayments();"><?= Main\Localization\Loc::getMessage('SPL_TEMPLETE_SALESCENTER_SEND_PAYMENT') ?></button>
<?php endif; ?>
<?php
$this->EndViewTarget();

$APPLICATION->IncludeComponent('bitrix:crm.order.payment.list', '', [
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
]);

$initJsData = [
	'gridId' => $arResult['grid']['fullId'],
	'context' => $arResult['context'],
	'sessionId' => $arParams['sessionId'],
	'isPaymentsLimitReached' => $arResult['isPaymentsLimitReached'],
];
$messages = Main\Localization\Loc::loadLanguageFile(__FILE__);
?>
	<script>
		BX.ready(function()
		{
			<?php if ($arResult['context'] === 'sms'): ?>
			BX.hide(document.getElementById('send_to_chat'));
			<?php endif; ?>
			BX.message(<?=CUtil::PhpToJSObject($messages)?>);
			BX.message(<?=CUtil::PhpToJSObject($arResult['messages'])?>);
			BX.Salescenter.Manager.init(<?=\CUtil::PhpToJSObject($arResult);?>);
			BX.Salescenter.Payments.init(<?=\CUtil::PhpToJSObject($initJsData);?>);
		});
	</script>
<?php

if ($arResult['isPaymentsLimitReached'] === true)
{
	CBitrix24::initLicenseInfoPopupJS('salescenterPaymentsLimit');
}

$APPLICATION->SetTitle(Main\Localization\Loc::getMessage('SPL_TEMPLETE_PAYMENT_TITLE'));
