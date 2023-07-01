<?php

use Bitrix\Main\EventManager;
use Bitrix\MobileApp\Mobile;
Mobile::Init();

$config = [
	'tabs' => [
		['code' => 'chat', 'class' => "\\Bitrix\\Mobile\\AppTabs\\Chat"],
		['code' => 'ol', 'class' => "\\Bitrix\\Mobile\\AppTabs\\OpenLines"],
		['code' => 'notify', "class" => "\\Bitrix\\Mobile\\AppTabs\\Notify"],
		['code' => 'stream', 'class' => "\\Bitrix\\Mobile\\AppTabs\\Stream"],
		['code' => 'task', 'class' => "\\Bitrix\\Mobile\\AppTabs\\Task"],
		['code' => 'menu', 'class' => "\\Bitrix\\Mobile\\AppTabs\\Menu"],
		['code' => 'crm', 'class' => \Bitrix\Mobile\AppTabs\Crm::class],
		['code' => 'terminal', 'class' => \Bitrix\Mobile\AppTabs\Terminal::class],
		['code' => 'catalog_store', 'class' => \Bitrix\Mobile\AppTabs\CatalogStore::class],
		['code' => 'projects', 'class' => \Bitrix\Mobile\AppTabs\Projects::class],
		['code' => 'calendar', 'class' => \Bitrix\Mobile\AppTabs\Calendar::class],
	],
	'required' => [
		'chat' => 100,
		'ol' => 150,
		'menu' => 1000,
	],
	'optional' => [
		'crm'
	],
	'unchangeable' => [
		'menu' => 1000,
	],
];

$config = array_merge($config, [
	'presetOptionalTabs' => [
		'task' => ["crm"],
		'stream' => ["crm"],
	],
	'defaultUserPreset' => [
		'chat' => 100,
		'stream' => 200,
		'task' => 300,
		'menu' => 1000,
	],
	'presets' => [
		'stream' => [
			'stream' => 100,
			'chat' => 150,
			'task' => 200,
			'crm' => 250,
			'menu' => 1000,
		],
		'task' => [
			'task' => 100,
			'chat' => 200,
			'stream' => 250,
			'crm' => 300,
			'menu' => 1000,
		],
		'crm' => [
			'crm' => 100,
			'chat' => 200,
			'task' => 300,
			'stream' => 350,
			'menu' => 1000,
		],
	]
]);

if (Mobile::getApiVersion() >= 49) {
	$config["presets"]["terminal"] = [
		'terminal' => 100,
		'chat' => 120,
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