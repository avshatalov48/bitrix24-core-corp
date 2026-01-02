<?php

use Bitrix\DiskMobile\AirDiskFeature;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Mobile\AppTabs\Calendar;
use Bitrix\Mobile\AppTabs\CatalogStore;
use Bitrix\Mobile\AppTabs\Chat;
use Bitrix\Mobile\AppTabs\Crm;
use Bitrix\Mobile\AppTabs\CrmCustomSectionFactory;
use Bitrix\Mobile\AppTabs\Disk;
use Bitrix\Mobile\AppTabs\Menu;
use Bitrix\Mobile\AppTabs\MenuNew;
use Bitrix\Mobile\AppTabs\Projects;
use Bitrix\Mobile\AppTabs\Stream;
use Bitrix\Mobile\AppTabs\Task;
use Bitrix\Mobile\AppTabs\Terminal;
use Bitrix\Mobile\Config\Feature;
use Bitrix\MobileApp\Mobile;
use Bitrix\Mobile\Feature\MenuFeature;

Mobile::Init();

$isDiskAvailable = (Loader::includeModule('diskmobile') && Feature::isEnabled(AirDiskFeature::class));

$config = [
	'tabs' => [
		['code' => 'chat', 'class' => Chat::class],
		['code' => 'stream', 'class' => Stream::class],
		['code' => 'task', 'class' => Task::class],
		['code' => 'menu', 'class' => Feature::isEnabled(MenuFeature::class) ? MenuNew::class : Menu::class],
		['code' => 'crm', 'class' => Crm::class],
		['code' => 'terminal', 'class' => Terminal::class],
		['code' => 'catalog_store', 'class' => CatalogStore::class],
		['code' => 'projects', 'class' => Projects::class],
		['code' => 'calendar', 'class' => Calendar::class],
		['code' => 'crmCustomSectionFactory', 'class' => CrmCustomSectionFactory::class],
		['code' => 'disk', 'class' => Disk::class],
	],
	'required' => [
		'chat' => 100,
		'menu' => 1000,
	],
	'optional' => [
		'crm',
		'menu' => 2000,
	],
	'unchangeable' => [
		'menu' => 1000,
	],
	'presetOptionalTabs' => [
		'task' => ['stream'],
		'stream' => ['crm'],
		'crm' => ['stream'],
	],
	'defaultUserPreset' => [
		'chat' => 100,
		'stream' => 200,
		'task' => 300,
		'menu' => 1000,
	],
	'defaultCollaberPreset' => [
		'chat' => 100,
		'disk' => 200,
		'calendar' => 300,
		'task' => 400,
		'menu' => 1000,
	],
	'presets' => [
		'task' => [
			'task' => 100,
			'chat' => 200,
			'stream' => 250,
			'calendar' => 300,
			'menu' => 1000,
		],
		'stream' => array_filter([
			'stream' => 100,
			'chat' => 150,
			'task' => 200,
			'crm' => (!$isDiskAvailable ? 250 : false),
			'disk' => ($isDiskAvailable ? 250 : false),
			'menu' => 1000,
		], 'intval'),
		'crm' => array_filter([
			'crm' => 100,
			'chat' => 200,
			'task' => 300,
			'calendar' => 350,
			'menu' => 1000,
		], 'intval'),
		'collaboration' => [
			'chat' => 100,
			'stream' => 150,
			'task' => 200,
			'calendar' => 250,
			'menu' => 1000,
		],
		'terminal' => [
			'terminal' => 100,
			'chat' => 120,
			'menu' => 1000,
		],
	],
	'presetsOptions' => [
		'manual' => [
			'sort' => 50,
			'messageCode' => 'TAB_PRESET_NAME_MANUAL_V2',
		],
		'collaboration' => [
			'sort' => 100,
			'messageCode' => 'TAB_PRESET_NAME_COLLABORATION',
		],
		'task' => [
			'sort' => 200,
			'messageCode' => 'TAB_PRESET_NAME_TASK_MSGVER_1',
		],
		'crm' => [
			'sort' => 300,
			'messageCode' => 'TAB_PRESET_NAME_CRM_V2',
		],
		'bizproc' => [
			'sort' => 400,
			'messageCode' => 'TAB_PRESET_NAME_BIZPROC',
		],
		'sign' => [
			'sort' => 500,
			'messageCode' => 'TAB_PRESET_NAME_SIGN',
		],
		'stream' => [
			'sort' => 600,
			'messageCode' => 'TAB_PRESET_NAME_STREAM_V2',
		],
		'terminal' => [
			'sort' => 700,
			'messageCode' => 'TAB_PRESET_NAME_TERMINAL',
		],
	],
];

if (Loader::includeModule('bizproc'))
{
	$config['presets']['bizproc'] = [
		'chat' => 100,
		'bizproc' => 150,
		'stream' => 200,
		'task' => 250,
		'menu' => 1000,
	];
}

if (
	Loader::includeModule('sign')
	&& !empty(EventManager::getInstance()->findEventHandlers('mobile', 'onBeforeTabsGet', ['signmobile']))
) {
	$config['presets']['sign'] = [
		'chat' => 100,
		'sign' => 150,
		'task' => 200,
		'stream' => 250,
		'menu' => 1000,
	];
}

foreach (EventManager::getInstance()->findEventHandlers('mobile', 'onBeforeTabsGet') as $event)
{
	$tabs = ExecuteModuleEventEx($event);
	if (is_array($tabs))
	{
		$config['tabs'] = array_merge($config['tabs'], $tabs);
	}
}

return $config;
