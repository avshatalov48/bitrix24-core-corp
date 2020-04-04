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
if($component->hasErrors())
{
	var_dump($component->getErrors());
}
?>

<? foreach($arResult['BREADCRUMBS'] as $crumb){ ?>
	<a href="<?= $crumb['LINK'] ?>"><?= $crumb['NAME'] ?></a>
<? }?>

<h2><?= $arResult['FOLDER']['NAME'] ?></h2>

<?= var_dump($arResult); ?>