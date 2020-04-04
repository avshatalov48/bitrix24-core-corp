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

<?
$APPLICATION->restartBuffer();
if($component->hasErrors())
{
	var_dump($component->getErrors());
}
?>

<? foreach($arResult['LINKS'] as $link) {?>
	<div>
		<a href="<?= $link['LINK'] ?>"><?= $link['LINK'] ?></a><br/>
		Скачано: <b><?= $link['DOWNLOAD_COUNT'] ?></b>
	</div>
<? }?>