<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.buttons',
]);

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/slider.css');
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/crm.css');
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/themes/.default/crm-entity-show.css');


if(SITE_TEMPLATE_ID === 'bitrix24')
{
	\Bitrix\Main\Page\Asset::getInstance()->addCss("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

$APPLICATION->RestartBuffer();
$jsObjName = 'barcodesSlider';

$inputTemplate =
	'<div class="crm-entity-widget-content-block-title">
		<span class="crm-entity-widget-content-block-title-text">&nbsp;</span>
	</div>
	<div class="crm-entity-widget-content-block-inner">
		<input class="crm-entity-widget-content-input" name="#BARCODE_ID#" type="text" value="#BARCODE#" onchange="'.$jsObjName.'.onBarcodeChange(this)">
	</div>';

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LANGUAGE_ID ?>" lang="<?=LANGUAGE_ID ?>">
<head>
	<script type="text/javascript">
		// Prevent loading page without header and footer
		if(window === window.top)
		{
			window.location = "<?=CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('IFRAME'))); ?>";
		}
	</script>
	<?$APPLICATION->ShowHead();?>
</head>
<body class="crm-iframe-popup crm-detail-page template-<?=SITE_TEMPLATE_ID?> crm-iframe-popup-no-scroll crm-order-shipment-product-list-barcode-wrapper <? $APPLICATION->ShowProperty('BodyClass'); ?>" onload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeLoad');" onunload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeUnload');">

<div class="crm-iframe-header">
	<div class="pagetitle-wrap">
		<div class="pagetitle-inner-container">
			<div class="pagetitle-menu" id="pagetitle-menu"><?
				$APPLICATION->ShowViewContent("pagetitle");
				$APPLICATION->ShowViewContent("inside_pagetitle");
				?></div>
			<div class="pagetitle">
				<span id="pagetitle" class="pagetitle-item"><?=\Bitrix\Main\Localization\Loc::getMessage('CRM_ORDER_SPLB_ENTER_BARCODES')?></span>
			</div>
		</div>
	</div>

	<div class="crm-iframe-workarea" id="crm-content-outer">
		<div class="crm-iframe-sidebar"><?$APPLICATION->ShowViewContent("sidebar"); ?></div>
		<div class="crm-iframe-content">
			<form id="crm-order-shipment-barcodes-form">
				<div class="crm-entity-card-container">

					<div class="crm-entity-card-container-content">
						<div class="crm-entity-card-widget-edit">
							<div class="crm-entity-widget-content">
								<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
									<div class="crm-entity-widget-content-block-draggable-btn-container">
										<div class="crm-entity-widget-content-block-draggable-btn"></div>
									</div>
									<div id="crm-order-shipment-barcodes-container">
									</div>
								</div>
								<div class="crm-entity-widget-content-block"></div>
							</div>
						</div>
					</div>

					<div class="crm-entity-wrap crm-section-control-active">
						<div class="crm-entity-section crm-entity-section-control">
							<button class="ui-btn ui-btn-success" title="[Ctrl+Enter]" onclick="<?=$jsObjName?>.onSave(); return false;"><?=Loc::getMessage('CRM_ORDER_SPLB_SAVE')?></button>
							<a href="#" class="ui-btn ui-btn-link" title="[Esc]" onclick="<?=$jsObjName?>.onCancel(); return false;"><?=Loc::getMessage('CRM_ORDER_SPLB_CANCEL')?></a>
						</div>
					</div>

			</form>
		</div>
	</div>

	<script type="text/javascript">
		<?=$jsObjName?> = BX.Crm.Order.Shipment.Product.Barcodes.create(
			{
				inputTemplate: '<?=CUtil::JSEscape($inputTemplate)?>',
				basketId: '<?=$arResult['BASKET_ID']?>',
				storeId: '<?=$arResult['STORE_ID']?>'
			}
		);

	</script>

</body>
</html>