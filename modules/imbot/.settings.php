<?php
return [
	'ui.entity-selector' => [
		'value' => [
			'entities' => [
				[
					'entityId' => 'imbot-network',
					'provider' => [
						'moduleId' => 'imbot',
						'className' => '\\Bitrix\\ImBot\\Integration\\Ui\\EntitySelector\\NetworkProvider',
					],
				],
			],
		],
	],
];
