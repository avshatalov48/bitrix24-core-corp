<?php
return [
	'controllers' => [
		'value' => [
			'defaultNamespace' => '\\Bitrix\\Voximplant\\Controller',
		],
		'readonly' => true,
	],
	'ui.entity-selector' => [
		'value' => [
			'entities' => [
				[
					'entityId' => 'voximplant_group',
					'provider' => [
						'moduleId' => 'voximplant',
						'className' => '\\Bitrix\\Voximplant\\Integration\\UI\\EntitySelector\\GroupProvider'
					],
				],
			],
			'extensions' => ['voximplant.entity-selector'],
		],
		'readonly' => true,
	]
];