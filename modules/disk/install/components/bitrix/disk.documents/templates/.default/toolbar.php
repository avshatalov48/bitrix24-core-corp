<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var $this CBitrixComponentTemplate */
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */

use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk;
use Bitrix\Main;
use Bitrix\UI;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Icon;
use Bitrix\UI\Buttons\Tag;

$documentRoot = Application::getDocumentRoot();
$isBitrix24Template = (SITE_TEMPLATE_ID === 'bitrix24');
$isInIframe = Main\Context::getCurrent()->getRequest()->get('IFRAME') === 'Y';

Toolbar::addFilter([
	'GRID_ID' => $arResult['GRID_ID'],
	'FILTER_ID' => $arResult['FILTER_ID'],
	'FILTER' => $arResult['FILTER'],
	'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
	'ENABLE_LIVE_SEARCH' => true,
	'ENABLE_LABEL' => true,
	'RESET_TO_DEFAULT_MODE' => false,
]);
Toolbar::setTitleMinWidth(158);

$defaultHandler = end($arResult['DOCUMENT_HANDLERS']);
$items = array_map(function ($item) use ($defaultHandler) {
	if ($item['code'] === OnlyOfficeHandler::getCode())
	{
		$defaultHandler = $item;
	}
	return [
		'text' => $item['name'],
		'code' => $item['code'],
		'items' => [
			[
				'text' => Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_CREATE_DOC'),
				'onclick' =>  new UI\Buttons\JsCode(
					'BX.Disk.Documents.Toolbar.createDocx("'.$item['code'].'");'
				)
			],
			[
				'text' => Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_CREATE_XLS'),
				'onclick' => new UI\Buttons\JsCode(
					'BX.Disk.Documents.Toolbar.createXlsx("'.$item['code'].'");'
				)
			],
			[
				'text' => Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_CREATE_PPT'),
				'onclick' => new UI\Buttons\JsCode(
					'BX.Disk.Documents.Toolbar.createPptx("'.$item['code'].'");'
				)
			],
		]
	];
}, $arResult['DOCUMENT_HANDLERS']);

if (\Bitrix\Main\Config\Option::get('disk', 'boards_enabled', 'N') === 'Y')
{
	array_unshift(
		$items,
		[
			'text' => Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_CREATE_BOARD'),
			'onclick' => new UI\Buttons\JsCode(
				'BX.Disk.Documents.Toolbar.createBoard();'
			)
		],
	);
}

$createButton = UI\Buttons\CreateButton::create([
	'dataset' => [
		'toolbar-collapsed-icon' => UI\Buttons\Icon::ADD
	]
]);

if ($arResult['VARIANT'] == Disk\Type\DocumentGridVariant::FlipchartList)
{
	$createButton
		->setText(Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_CREATE_BOARD_TEXT'))
		->setColor(UI\Buttons\Color::SUCCESS)
		->addClass('toolbar-button-create-new-board')
		->bindEvent(
			'click',
			new UI\Buttons\JsCode('BX.Disk.Documents.Toolbar.createBoard();')
		);
}
else
{
	$createButton
		->setText(Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_CREATE_DOC_TEXT'))
		->setColor(UI\Buttons\Color::SUCCESS)
		->setMenu([
			'items' => $items
		]);
}

Toolbar::addButton($createButton, UI\Toolbar\ButtonLocation::AFTER_TITLE);

Toolbar::addButton([
	'color' => UI\Buttons\Color::LIGHT_BORDER,
	'link' => $arResult['PATH_TO_DISK'],
	'text' => Loc::getMessage('DISK_DOCUMENTS_MY_LIBRARY'),
	'icon' => UI\Buttons\Icon::DISK,
]);
$settings = Toolbar::addButton([
	'color' => UI\Buttons\Color::LIGHT_BORDER,
	'icon' => UI\Buttons\Icon::SETTING,
	'dropdown' => false,
	'menu' => [
		'items' => [
			[
				'text' => Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_SETTINGS'),
				'onclick' => new UI\Buttons\JsHandler('BX.Disk.InformationPopups.openWindowForSelectDocumentService')
			]
		]
	]
]);
Toolbar::addButton([
	"color" => Color::LIGHT_BORDER,
	"tag" => Tag::LINK,
	"className" => 'js-disk-trashcan-button',
	"dataset" => ['toolbar-collapsed-icon' => Icon::REMOVE],
	"link" => $arResult['PATH_TO_TRASHCAN_LIST'],
	"text" => Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_TRASH'),
]);