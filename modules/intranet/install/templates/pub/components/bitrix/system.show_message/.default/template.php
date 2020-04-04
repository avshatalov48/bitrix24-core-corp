<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;
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
/** @var CBitrixComponent $component */
$this->setFrameMode(true);

$APPLICATION->SetTitle(Loc::getMessage("ERR_DEFAULT"));
?>

<div id="pub-template-error" class="error-block" style="display: block;">
	<div class="pub-template-error-image"></div>
	<div id="pub-template-error-title" class="error-block-title"><?=Loc::getMessage("ERR_SOME")?></div>
	<div id="pub-template-error-text" class="error-block-text"><?=$arParams["MESSAGE"]?></div>
</div>