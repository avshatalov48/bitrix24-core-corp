<?
use \Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadLanguageFile(__FILE__);

/** @var \Bitrix\SalesCenter\Delivery\Handlers\Base $handler */
$handler = $arResult['handler'];

/** @var \Bitrix\SalesCenter\Delivery\Wizard\YandexTaxi $yandexTaxiWizard */
$yandexTaxiWizard = $handler->getWizard();

$currentRegion = $yandexTaxiWizard->getYandexTaxiRegionFinder()->getCurrentRegion();

if ($currentRegion === 'kz')
{
	$signUpLink = 'https://forms.yandex.ru/surveys/10019070.60ddd556d74d7be7008fd08cb09e7860f4e2edef/?ya_medium=module&ya_campaign=bitrix24';
}
elseif ($currentRegion === 'by')
{
	$signUpLink = 'https://delivery.yandex.com/by-ru?ya_medium=module&ya_campaign=bitrix24#form';
}
else
{
	$signUpLink = 'https://dostavka.yandex.ru/reg/?ya_medium=module&amp;ya_campaign=bitrix24';
}
?>

<div class="salescenter-delivery-install-section">
	<div class="salescenter-delivery-install-logo-block">
		<div class="salescenter-delivery-install-logo"></div>
	</div>
	<div class="salescenter-delivery-install-content-block">
		<h2><?=$handler->getName()?></h2>
		<p><?=Loc::getMessage('DELIVERY_SERVICE_YANDEX_TAXI_CONNECT_TEXT')?></p>
		<a href="https://helpdesk.bitrix24.ru/open/11604358" class="ui-link ui-link-dashed">
			<?=Loc::getMessage('DELIVERY_SERVICE_CONNECT_TEXT_MORE', ['#SERVICE_NAME#' => $handler->getName()])?>
		</a>
		<div class="salescenter-delivery-install-btn-container">
			<button onclick="window.open('<?=$signUpLink?>','_blank');return false;" class="ui-btn ui-btn-primary">
				<?=Loc::getMessage('DELIVERY_SERVICE_YANDEX_TAXI_SIGN_UP', ['#SERVICE_NAME#' => $handler->getName()])?>
			</button>
		</div>
	</div>
</div>
