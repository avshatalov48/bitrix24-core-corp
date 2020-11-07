<?
use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>

<div class="salescenter-delivery-install-section">
	<div class="salescenter-delivery-install-logo-block">
		<div class="salescenter-delivery-install-logo"></div>
	</div>
	<div class="salescenter-delivery-install-content-block">
		<h2><?=Loc::getMessage('DELIVERY_SERVICE_YANDEX_TAXI_TITLE')?></h2>
		<p><?=Loc::getMessage('DELIVERY_SERVICE_YANDEX_TAXI_CONNECT_TEXT')?></p>
		<a href="https://helpdesk.bitrix24.ru/open/11604358" class="ui-link ui-link-dashed">
			<?=Loc::getMessage('DELIVERY_SERVICE_YANDEX_TAXI_CONNECT_TEXT_MORE')?>
		</a>
		<div class="salescenter-delivery-install-btn-container">
			<button onclick="window.open('//taxi.yandex.ru/business/registration?ya_source=businessdelivery&ya_medium=module&ya_campaign=bitrix24&#form','_blank');return false;" class="ui-btn ui-btn-primary">
				<?=Loc::getMessage('DELIVERY_SERVICE_YANDEX_TAXI_SIGN_UP')?>
			</button>
		</div>
	</div>
</div>
