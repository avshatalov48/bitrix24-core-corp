<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Intranet\Site\Sections\TimemanSection;
use Bitrix\Landing\Rights;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/company/.left.menu_ext.php');
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/.superleft.menu_ext.php');

$GLOBALS['APPLICATION']->setPageProperty('topMenuSectionDir', '/company/');

$aMenuLinks = [
	[
		Loc::getMessage('MENU_STRUCTURE'),
		'/company/vis_structure.php',
		[],
		[
			'menu_item_id' => 'menu_company',
		],
		'',
	],
	[
		Loc::getMessage('MENU_EMPLOYEE'),
		'/company/',
		[],
		[
			'menu_item_id' => 'menu_employee',
		],
		'',
	],
];

if (Loader::includeModule('intranet') && TimemanSection::isAvailable())
{
	$aMenuLinks[] = TimemanSection::getRootMenuItem();
}

$landingIncluded = Loader::includeModule('landing');
if ($landingIncluded && Rights::hasAdditionalRight(Rights::ADDITIONAL_RIGHTS['menu24'], 'knowledge'))
{
	$aMenuLinks[] = [
		Loc::getMessage('MENU_KNOWLEDGE_BASE'),
		'/kb/',
		[],
		[
			'menu_item_id' => 'menu_knowledge',
		],
		'',
	];
}

if (\Bitrix\Main\ModuleManager::isModuleInstalled('im'))
{
	$aMenuLinks[] = [
		Loc::getMessage('MENU_CONFERENCE_SECTION'),
		'/conference/',
		[],
		[
			'menu_item_id' => 'menu_conference',
		],
		''
	];
}
