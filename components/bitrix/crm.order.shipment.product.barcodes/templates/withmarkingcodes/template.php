<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @global CMain $APPLICATION
 * @var array $arResult
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;

Loc::loadMessages(__FILE__);

Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.buttons',
	'sale.barcode',
]);

Asset::getInstance()->addCss('/bitrix/js/crm/css/slider.css');
Asset::getInstance()->addCss('/bitrix/js/crm/css/crm.css');
Asset::getInstance()->addCss('/bitrix/themes/.default/crm-entity-show.css');

if($arResult['ADDITIONAL_CSS_PATH'] <> '')
{
	Asset::getInstance()->addCss($arResult['ADDITIONAL_CSS_PATH']);
}

Asset::getInstance()->addCss("/bitrix/themes/.default/bitrix24/crm-entity-show.css");

$APPLICATION->RestartBuffer();
$jsObjName = 'barcodesSlider';

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LANGUAGE_ID ?>" lang="<?=LANGUAGE_ID ?>">
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
</head>
<body class="crm-iframe-popup crm-detail-page template-<?=SITE_TEMPLATE_ID?> crm-iframe-popup-no-scroll crm-order-shipment-product-list-barcode-wrapper <?php $APPLICATION->ShowProperty('BodyClass'); ?>" onload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeLoad');" onunload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeUnload');">

<div class="crm-iframe-header">
	<div class="pagetitle-wrap">
		<div class="pagetitle-inner-container">
			<div class="pagetitle">
				<span id="pagetitle" class="pagetitle-item"><?=\Bitrix\Main\Localization\Loc::getMessage('CRM_ORDER_SPLB_ENTER_BARCODES')?></span>
			</div>
		</div>
	</div>

	<div class="crm-iframe-workarea" id="crm-content-outer">
		<div class="crm-iframe-sidebar"><?php
			$APPLICATION->ShowViewContent("sidebar");
		?></div>
		<div class="crm-iframe-content">
			<form id="crm-order-shipment-barcodes-form">
				<div class="crm-entity-card-container">
					<div id="crm-order-shipment-product-barcode-content-root"></div>
					<div class="crm-entity-wrap crm-section-control-active">
						<div class="crm-entity-section crm-entity-section-control">
							<button class="ui-btn ui-btn-success" title="[Ctrl+Enter]" onclick="<?=$jsObjName?>.onSave(); return false;"><?=Loc::getMessage('CRM_ORDER_SPLB_SAVE')?></button>
							<a href="#" class="ui-btn ui-btn-link" title="[Esc]" onclick="<?=$jsObjName?>.onCancel(); return false;"><?=Loc::getMessage('CRM_ORDER_SPLB_CANCEL')?></a>
							<div class="crm-entity-section-control-error-block"></div>
						</div>
					</div>

			</form>
		</div>
	</div>

	<script>
		<?=$jsObjName?> = BX.Crm.Order.Shipment.Product.Barcodes.create(
			{
				contentContainerId: 'crm-order-shipment-product-barcode-content-root',
				basketId: '<?=(int)$arResult['BASKET_ID']?>',
				storeId: '<?=(int)$arResult['STORE_ID']?>'
			}
		);

	</script>

</body>
</html>
