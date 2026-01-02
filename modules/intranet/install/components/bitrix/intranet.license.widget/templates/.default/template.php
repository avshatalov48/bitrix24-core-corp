<?php
/** @var array $arResult */

use Bitrix\Main\UI\Extension;
use Bitrix\Intranet\License;
use Bitrix\Main\Web\Json;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Extension::load(['intranet.widget-loader']);

$frame = $this->createFrame()->begin();
$mainButton = new License\Widget\Content\MainButton();
$license = new License\Widget\Content\License();
?>

<span data-id="licenseWidgetWrapper">
	<button class="<?= $mainButton->getClassName() ?>">
		<span class="license-btn-icon"></span>
		<span class="license-btn-counter"></span>
		<?php if ($license->isExpired()): ?>
			<span class="license-btn-icon-battery">
					<span class="license-btn-icon-battery-full">
						<span class="license-btn-icon-battery-inner">
							<span></span>
							<span></span>
							<span></span>
						</span>
					</span>
					<svg class="license-btn-icon-battery-cross" xmlns="http://www.w3.org/2000/svg" width="22" height="18">
						<path fill="#FFF" fill-rule="evenodd" d="M18.567.395c.42.42.42 1.1 0 1.52l-1.04 1.038.704.001a2 2 0 012 2v1.799a1.01 1.01 0 01.116-.007H21a1 1 0 011 1v2.495a1 1 0 01-1 1h-.653l-.116-.006v1.798a2 2 0 01-2 2L5.45 15.032l-2.045 2.045a1.075 1.075 0 11-1.52-1.52L17.047.395c.42-.42 1.1-.42 1.52 0zm-2.583 4.102l-8.991 8.99 10.836.002a1 1 0 00.994-.883l.006-.117v-6.99a1 1 0 00-1-1l-1.845-.002zm-5.031-1.543L9.409 4.498h-6.23a1 1 0 00-.993.884l-.006.116-.001 6.23-1.4 1.398v-.046L.777 4.954a2 2 0 012-2h8.175z"/>
					</svg>
			</span>
		<?php endif; ?>
		<span class="license-btn-name"><?= $mainButton->getText() ?></span>
	</button>
</span>

<script>
	BX.ready(() => {
		const buttonWrapper = document.querySelector("[data-id='licenseWidgetWrapper']");
		BX.bind(buttonWrapper, 'click', () => {
			BX.unbindAll(buttonWrapper);
			const widgetLoader = new BX.Intranet.WidgetLoader({
				bindElement: buttonWrapper,
				width: 374,
				id: 'bx-license-header-popup',
			});
			widgetLoader.createSkeletonFromConfig(<?= Json::encode($arResult['skeleton']) ?>).show();
			const dataPromise = BX.ajax.runAction('intranet.license.widget.getContent');
			BX.loadExt(['intranet.license-widget']).then(() => {
				dataPromise.then((response) => {
					widgetLoader.clearBeforeInsertContent();
					BX.Intranet.LicenseWidget.getInstance().setOptions({
						buttonWrapper: buttonWrapper,
						loader: widgetLoader.getPopup(),
						data: response.data,
					}).show();
				});
			});
		});
	});
</script>

<?php
$frame->beginStub();
?>

<span data-id="licenseWidgetWrapper">
	<button class="<?= $mainButton->getClassName() ?>">
		<span class="license-btn-icon"></span>
		<span class="license-btn-counter"></span>
		<span class="license-btn-name"><?= $mainButton->getText() ?></span>
	</button>
</span>

<?php
$frame->end();
?>