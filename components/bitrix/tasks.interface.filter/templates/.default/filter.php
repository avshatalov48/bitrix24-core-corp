<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
?>

<div class="tasks-interface-filter pagetitle-container<?php if (!$isBitrix24Template): ?> pagetitle-container-light<? endif ?> pagetitle-flexible-space">
	<?php
	$filterComponentData = [
		"FILTER_ID" => $arParams["FILTER_ID"],
		"GRID_ID" => $arParams["GRID_ID"],
		"FILTER" => $arParams["FILTER"],
		"FILTER_PRESETS" => $arParams["PRESETS"],
		"ENABLE_LABEL" => true,
		'ENABLE_LIVE_SEARCH' => $arParams['USE_LIVE_SEARCH'] == 'Y',
		'RESET_TO_DEFAULT_MODE' => true,
		'THEME' => Bitrix\Main\UI\Filter\Theme::LIGHT,
	];

	if ($arResult['LIMIT_EXCEEDED'])
	{
		$filterComponentData['LIMITS'] = $arResult['LIMITS'];
	}

	$APPLICATION->IncludeComponent(
		"bitrix:main.ui.filter",
		"",
		$filterComponentData,
		$component,
		["HIDE_ICONS" => true]
	); ?>
</div>
