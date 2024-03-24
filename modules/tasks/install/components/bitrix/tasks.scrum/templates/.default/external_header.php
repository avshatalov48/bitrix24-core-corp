<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var $APPLICATION \CMain */
/** @var array $arResult */
/** @var array $arParams */
/** @var \CBitrixComponent $component */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;

Extension::load([
	'tasks',
	'ui.forms',
	'ui.layout-form',
	'ui.buttons.icons',
	'ui.cnt',
	'ui.dialogs.messagebox',
	'ui.icons.b24',
	'ui.draganddrop.draggable',
	'ui.label',
	'ui.entity-selector',
	'ui.short-view',
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.analytics',
]);

Extension::load('date');
Extension::load('sidepanel');
Extension::load('popup');

if (Loader::includeModule('pull'))
{
	Extension::load('pull.client');
}

if (Loader::includeModule('disk'))
{
	Asset::getInstance()->addJs('/bitrix/components/bitrix/disk.uf.file/templates/.default/script.js');

	Extension::load([
		'file_dialog',
		'mobile_uploader',
		'disk.document',
		'disk_external_loader',
		'uploader',
	]);
}


$isKanban = $isKanban ?? false;
$viewName = $this->getComponent()->getTemplatePage();

$messages = Loc::loadLanguageFile(__DIR__ . '/header.php');

/** @var Filter $filterInstance */
$filterInstance = $arResult['filterInstance'];

$filterId = $filterInstance->getId();

?>
