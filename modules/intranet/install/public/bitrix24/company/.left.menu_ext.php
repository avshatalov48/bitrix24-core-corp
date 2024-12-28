<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Intranet\Site\Sections\TimemanSection;
use Bitrix\Landing\Rights;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/company/.left.menu_ext.php');
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/.superleft.menu_ext.php');
$GLOBALS['APPLICATION']->setPageProperty('topMenuSectionDir', '/company/');
$aMenuLinks = [];

if (ToolsManager::getInstance()->checkAvailabilityByMenuId('menu_employee'))
{
	$aMenuLinks[] = [
		Loc::getMessage('MENU_EMPLOYEE'),
		'/company/',
		[],
		[
			'menu_item_id' => 'menu_employee',
			'counter_num' => (new \Bitrix\Intranet\User())->getTotalInvitationCounterValue(),
			'counter_id' => \Bitrix\Intranet\Invitation::getTotalInvitationCounterId()
		],
		'',
	];
}

if (ToolsManager::getInstance()->checkAvailabilityByMenuId('menu_company'))
{
	$aMenuLinks[] = [
		Loc::getMessage('MENU_STRUCTURE'),
		'/hr/structure/',
		[],
		[
			'menu_item_id' => 'menu_company',
		],
		'',
	];
}

if (Loader::includeModule('intranet') && TimemanSection::isAvailable())
{
	$aMenuLinks[] = TimemanSection::getRootMenuItem();
}

$landingIncluded = Loader::includeModule('landing');

if (
	$landingIncluded
	&& Rights::hasAdditionalRight(Rights::ADDITIONAL_RIGHTS['menu24'], 'knowledge')
	&& ToolsManager::getInstance()->checkAvailabilityByMenuId('menu_knowledge')
)
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

if (
	\Bitrix\Main\ModuleManager::isModuleInstalled('im')
	&& ToolsManager::getInstance()->checkAvailabilityByMenuId('menu_conference')
)
{
	$aMenuLinks[] = [
		Loc::getMessage('MENU_CONFERENCE_SECTION'),
		'/conference/',
		[],
		[
			'menu_item_id' => 'menu_conference',
		],
		'',
	];
}
