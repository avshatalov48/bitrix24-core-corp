<?php

/**
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CBitrixComponent $component
 * @var CBitrixComponentTemplate $this
 */

$APPLICATION->SetTitle('');

$APPLICATION->IncludeComponent(
	'bitrix:ui.info.error',
	'',
	[
		'TITLE' => $arResult['ERROR_TITLE'],
	]
);
