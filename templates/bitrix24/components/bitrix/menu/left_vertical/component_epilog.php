<?

use Bitrix\Main\UI\Extension;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var @global CMain $APPLICATION */
/** @var CBitrixComponent $this  */
global $APPLICATION;
$APPLICATION->SetAdditionalCSS($this->getTemplate()->getFolder()."/groups.css");
$APPLICATION->SetAdditionalCSS($this->getTemplate()->getFolder()."/map.css");
$APPLICATION->AddHeadScript($this->getTemplate()->getFolder()."/map.js");
$APPLICATION->AddHeadScript("/bitrix/js/main/dd.js");

Extension::load(["ajax", "fx", "ui.design-tokens"]);