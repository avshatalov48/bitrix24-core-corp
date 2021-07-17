<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \CMain */
global $APPLICATION;
/** @var array $arParams */
/** @var array $arResult */

$APPLICATION->IncludeComponent(
	'bitrix:main.file.input',
	'mobile',
	[
		'MODULE_ID' => 'bizproc',
		'ALLOW_UPLOAD' => 'A',
		'INPUT_NAME' => $arResult['inputName'],
		'INPUT_VALUE' => $arResult['inputValue'],
		'MULTIPLE' => 'Y'
	]
);
?>
<input
		type="hidden"
		name="<?= htmlspecialcharsbx($arResult['originalInputName'])?>"
		value="<?= htmlspecialcharsbx($arResult['originalInputValue']) ?>"
>
