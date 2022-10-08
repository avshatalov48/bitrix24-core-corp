<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

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

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/template.php');
Loc::loadMessages(__FILE__);

\CJSCore::Init(array('core', 'update_stepper'));

\Bitrix\Main\UI\Extension::load(['ui.alerts', 'ui.fonts.opensans']);

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/sale/core_ui_widget.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/sale/core_iterator.js');


/** @var \CDiskVolumeComponent $component */
$component = $this->getComponent();


$APPLICATION->setTitle(Loc::getMessage('CRM_VOLUME_TITLE'));


$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";

if ($isBitrix24Template)
{
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'pagetitle-toolbar-field-view tasks-pagetitle-view');
}


