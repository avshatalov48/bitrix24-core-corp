<?php
return [
	"tabs" => [
		["code" => "chat", "class" => "\\Bitrix\\Mobile\\AppTabs\\Chat"],
		["code" => "ol", "class" => "\\Bitrix\\Mobile\\AppTabs\\OpenLines"],
		["code" => "menu", "class" => "\\Bitrix\\Mobile\\AppTabs\\Menu"],
//		["code" => "notify", "class" => "\\Bitrix\\Mobile\\AppTabs\\Notify"],
		["code" => "stream", "class" => "\\Bitrix\\Mobile\\AppTabs\\Stream"],
		["code" => "task", "class" => "\\Bitrix\\Mobile\\AppTabs\\Task"],
	],
	"required" => [
		"chat" => 100,
		"ol" => 150,
		"menu" => 1000,
	],
	"unchangeable" => [
		"menu" => 1000,
	],
	"presetCondition" => [
		"ol" => ["requiredTabs" => ["ol"]]
	],
	"presets" => [
		"default" => [
			"chat" => 100,
			"stream" => 300,
//			"notify" => 300,
			"task" => 200,
			"menu" => 1000,
		],
		"ol" => [
			"chat" => 100,
			"ol" => 150,
			"stream" => 200,
//			"notify" => 300,
			"menu" => 1000,
		],
		"stream" => [
			"stream" => 100,
			"chat" => 150,
			"task" => 200,
//			"notify" => 300,
			"menu" => 1000,
		],
		"task" => [
			"task" => 100,
//			"notify" => 150,
			"chat" => 200,
			"menu" => 1000,
		]
	]
];