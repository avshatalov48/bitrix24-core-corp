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
use Bitrix\Mobile\AppTabs\Notify;
use Bitrix\Mobile\AppTabs\OpenLines;
use Bitrix\Mobile\AppTabs\Projects;
use Bitrix\Mobile\AppTabs\Stream;
use Bitrix\Mobile\AppTabs\Task;
use Bitrix\Mobile\AppTabs\Terminal;
use Bitrix\Mobile\Config\Feature;
use Bitrix\MobileApp\Mobile;

Mobile::Init();

$isDiskAvailable = (Loader::includeModule('diskmobile') && Feature::isEnabled(AirDiskFeature::class));
$config = [
	'tabs' => [
		['code' => 'chat', 'class' => Chat::class],
		['code' => 'ol', 'class' => OpenLines::class],
		['code' => 'notify', 'class' => Notify::class],
		['code' => 'stream', 'class' => Stream::class],
		['code' => 'task', 'class' => Task::class],
		['code' => 'menu', 'class' => Menu::class],
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
		'ol' => 150,
		'menu' => 1000,
	],
	'optional' => [
		'crm',
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
			'stream' => (!$isDiskAvailable ? 350 : false),
			'disk' => ($isDiskAvailable ? 350 : false),
			'menu' => 1000,
		], 'intval'),
		'collaboration' => [
			'chat' => 100,
			'stream' => 150,
			'task' => 200,
			'calendar' => 250,
			'menu' => 1000,
		],
		'team_communication' => [
			'chat' => 100,
			'stream' => 150,
			'task' => 200,
			'menu' => 1000,
		],
		'terminal' => [
			'terminal' => 100,
			'chat' => 120,
			'menu' => 1000,
		],
	],
];

foreach (EventManager::getInstance()->findEventHandlers('mobile', 'onBeforeTabsGet') as $event)
{
	$tabs = ExecuteModuleEventEx($event);
	if (is_array($tabs))
	{
		$config['tabs'] = array_merge($config['tabs'], $tabs);
	}
}

return $config;
