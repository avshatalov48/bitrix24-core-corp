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

$mainButton = new License\Widget\Content\MainButton();
?>

<div class="air-header__button" data-id="licenseWidgetWrapper">
	<button class="air-header-button">
		<span class="air-header-button__text"><?= $mainButton->getText() ?></span>
		<span class="air-header-button__counter"></span>
	</button>
</div>

<?php
$frame = $this->createFrame()->begin('');
?>
<script>
	BX.ready(() => {
		BX.Intranet.Bitrix24.LicenseButton.init({
			isAdmin: true,
			isCloud: false,
			skeleton: <?= Json::encode($arResult['skeleton']) ?>,
		});
	});
</script>

<?php
$frame->end();

