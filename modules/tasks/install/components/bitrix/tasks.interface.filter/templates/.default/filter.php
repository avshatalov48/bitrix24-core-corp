<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\UI\Filter\Theme;

$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";
$filterId = $arParams["FILTER_ID"] ?? null;
?>

<div class="tasks-interface-filter pagetitle-container<?php if (!$isBitrix24Template): ?> pagetitle-container-light<? endif ?> pagetitle-flexible-space">
	<?php
	$filterComponentData = [
		"FILTER_ID" => $filterId,
		"GRID_ID" => $arParams["GRID_ID"] ?? null,
		"FILTER" => $arParams["FILTER"] ?? null,
		"FILTER_PRESETS" => $arParams["PRESETS"] ?? null,
		"ENABLE_LABEL" => true,
		'ENABLE_LIVE_SEARCH' => isset($arParams['USE_LIVE_SEARCH']) && $arParams['USE_LIVE_SEARCH'] === 'Y',
		'RESET_TO_DEFAULT_MODE' => true,
		'THEME' => Theme::MUTED,
	];

	if (isset($arResult['LIMIT_EXCEEDED']))
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
