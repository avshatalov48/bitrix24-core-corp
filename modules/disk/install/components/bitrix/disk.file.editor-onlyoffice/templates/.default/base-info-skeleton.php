<?php

use Bitrix\Main\Context;

\Bitrix\Main\UI\Extension::load(['ui.design-tokens']);

$headerLogoClass = '';
if (Context::getCurrent()->getLanguage() !== 'ru')
{
	$headerLogoClass = 'disk-fe-office-header-logo--eng';
}
?>
<div data-id="<?= $containerId ?>-wrapper">
	<div class="disk-fe-office-header">
		<div class="disk-fe-office-header-left">
			<a href="<?= $arResult['HEADER_LOGO_LINK'] ?>" class="disk-fe-office-header-logo <?= $headerLogoClass ?>" target="_blank"></a>
			<div class="disk-fe-office-header-mode">
				<span class="disk-fe-office-header-mode-text"><?= $headerText ?></span>
			</div>
		</div>
	</div>
	<div data-id="<?= $containerId ?>">
		<div data-id="<?= $containerId ?>-base" style="height: calc(100vh - 70px)"></div>
	</div>
</div>