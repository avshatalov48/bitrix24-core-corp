<?
use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

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
		</label>
		<div class="ui-ctl ui-ctl-textbox ui-ctl-w75" style="margin-bottom: 17px;">
			<input value="<?=htmlspecialcharsbx($tokenValue)?>" placeholder="<?=Loc::getMessage('DELIVERY_SERVICE_YANDEX_TAXI_TOKEN_PLACEHOLDER')?>" required type="text" name="OAUTH_TOKEN" class="ui-ctl-element" />
		</div>
		<a href="https://helpdesk.bitrix24.ru/open/11604358" class="ui-link ui-link-dashed">
			<?=Loc::getMessage('DELIVERY_SERVICE_YANDEX_TAXI_WHERE_TO_FIND_TOKEN')?>
		</a>
	</div>
</div>
