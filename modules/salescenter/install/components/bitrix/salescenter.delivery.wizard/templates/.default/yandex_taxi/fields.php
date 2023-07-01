<?
use \Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadLanguageFile(__FILE__);

/** @var \Bitrix\SalesCenter\Delivery\Handlers\Base $handler */
$handler = $arResult['handler'];

$tokenValue = $arResult['edit']
	? (isset($arResult['service']['CONFIG']['MAIN']['OAUTH_TOKEN']) ? $arResult['service']['CONFIG']['MAIN']['OAUTH_TOKEN'] : ''):
	'';
?>

<div class="salescenter-delivery-install-section-no-bottom-margin">
	<div class="salescenter-delivery-install-content-block">
		<h2 class="sales-center-delivery-install-title">
			<?=Loc::getMessage('DELIVERY_SERVICE_YANDEX_TAXI_TOKEN')?>
		</h2>
		<label for="" class="ui-ctl-label-text">
			<?=Loc::getMessage('DELIVERY_SERVICE_YANDEX_TAXI_TOKEN_LABEL')?>
			(<?=Loc::getMessage('DELIVERY_SERVICE_ACCOUNT_BALANCE_NOTICE', ['#SERVICE_NAME#' => $handler->getName()])?>)
		</label>
		<div class="ui-ctl ui-ctl-textbox ui-ctl-w75" style="margin-bottom: 17px;">
			<input value="<?=htmlspecialcharsbx($tokenValue)?>" placeholder="y2_AgAAAAD0Wcn4AAAPeAAAAAACJXtV-u9qs8IzQzWzJ0Cdt9pv-Wh1YS8" required type="text" name="OAUTH_TOKEN" class="ui-ctl-element" />
		</div>
		<a href="https://helpdesk.bitrix24.ru/open/11604358" class="ui-link ui-link-dashed">
			<?=Loc::getMessage('DELIVERY_SERVICE_YANDEX_TAXI_WHERE_TO_FIND_TOKEN')?>
		</a>
	</div>
</div>
