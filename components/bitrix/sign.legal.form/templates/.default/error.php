<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var string $componentPath
 */
?>
<?php
/** @var Error $error */
foreach ($component->getErrors() as $error):
?>
	<div style="color:red"><?= htmlspecialcharsbx($error->getMessage()) ?></div>
<?php endforeach;?>
