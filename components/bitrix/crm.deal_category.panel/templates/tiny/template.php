<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;

Bitrix\Main\UI\Extension::load([
		'ui.buttons',
		'main.popup',
]);

$guid = $arResult['GUID'];
$containerId = HtmlFilter::encode("{$guid}_container");
$buttonId = HtmlFilter::encode("{$guid}_selector");
$counterId = HtmlFilter::encode("{$guid}_counter");
?>

<div id="<?= $containerId; ?>" class="crm-interface-toolbar-button-container">
	<button id="<?= $buttonId; ?>" class="ui-btn ui-btn-themes ui-btn-light-border<?= count($arResult['ITEMS']) > 0 ? ' ui-btn-dropdown' : ''?>">
		<span class="ui-btn-text"><?= $arResult['CATEGORY_NAME'] ? $arResult['CATEGORY_NAME'] : htmlspecialcharsbx($arResult['ITEMS'][0]['NAME']); ?></span>
		<? if ($arResult['CATEGORY_COUNTER'] > 0) : ?>
			<i id="<?= $counterId; ?>" class="ui-btn-counter"><?= $arResult['CATEGORY_COUNTER']; ?></i>
		<? endif; ?>
	</button>
</div>

<script>
	BX.message(<?=CUtil::phpToJsObject(Loc::loadLanguageFile(__FILE__))?>);

	void new BX.Crm.Deal.Category.Panel({
		button: document.getElementById('<?= $buttonId; ?>'),
		counter: document.getElementById('<?= $counterId; ?>'),
		container: document.getElementById('<?= $containerId; ?>'),
		items: <?= CUtil::PhpToJSObject($arResult['ITEMS']); ?>,
		tunnelsUrl: '/crm/tunnels/',
		componentParams: <?= CUtil::PhpToJSObject($arParams); ?>
	});
</script>

