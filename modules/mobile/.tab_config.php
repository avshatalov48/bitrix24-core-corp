<?php

use Bitrix\Main\EventManager;
use Bitrix\MobileApp\Mobile;

$config = [
	'tabs' => [
		['code' => 'chat', 'class' => "\\Bitrix\\Mobile\\AppTabs\\Chat"],
		['code' => 'ol', 'class' => "\\Bitrix\\Mobile\\AppTabs\\OpenLines"],
		['code' => 'notify', "class" => "\\Bitrix\\Mobile\\AppTabs\\Notify"],
		['code' => 'stream', 'class' => "\\Bitrix\\Mobile\\AppTabs\\Stream"],
		['code' => 'task', 'class' => "\\Bitrix\\Mobile\\AppTabs\\Task"],
		['code' => 'menu', 'class' => "\\Bitrix\\Mobile\\AppTabs\\Menu"],
		['code' => 'calltracker', 'class' => "\\Bitrix\\Mobile\\AppTabs\\CallTracker"],
	],
	'required' => [
		'chat' => 100,
		'ol' => 150,
		'menu' => 1000,
	],
	'unchangeable' => [
		'menu' => 1000,
	],
];

if (Mobile::getInstance()  && Mobile::getApiVersion() >= 41)
{
	$config = array_merge($config, [
		'presets' => [
			'default' => [
				'chat' => 100,
				'stream' => 200,
				'task' => 300,
				'menu' => 1000,
			],
			'stream' => [
				'stream' => 100,
				'chat' => 150,
				'task' => 200,
				'menu' => 1000,
			],
			'task' => [
				'task' => 100,
				'chat' => 200,
				'menu' => 1000,
			]
		]
	]);
}
else
{
	$config = array_merge($config, [
		'presetCondition' => [
			'ol' => ['requiredTabs' => ['ol']]
		],
		'presets' => [
			'default' => [
				'chat' => 100,
				'stream' => 200,
				'notify' => 200,
				'task' => 300,
				'menu' => 1000,
			],
			'ol' => [
				'chat' => 100,
				'ol' => 150,
				'notify' => 200,
				'stream' => 200,
				'menu' => 1000,
			],
			'stream' => [
				'stream' => 100,
				'chat' => 150,
				'notify' => 200,
				'task' => 200,
				'menu' => 1000,
			],
			'task' => [
				'task' => 100,
				'notify' => 110,
				'chat' => 120,
				'menu' => 1000,
			]
		]
	]);
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