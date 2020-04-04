<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \Bitrix\Disk\Internals\BaseComponent $component */
?>

<?php

$APPLICATION->IncludeComponent(
	'bitrix:disk.folder.view',
	'',
	array_merge(array_intersect_key($arResult, array(
		'STORAGE' => true,
		'PATH_TO_FOLDER_LIST' => true,
		'PATH_TO_FILE_VIEW' => true,
	)), array(
		'FOLDER_ID' => $arResult['VARIABLES']['FOLDER_ID'],
		'RELATIVE_PATH' => $arResult['VARIABLES']['RELATIVE_PATH'],
		'RELATIVE_ITEMS' => $arResult['VARIABLES']['RELATIVE_ITEMS'],
	)),
	$component
);?>