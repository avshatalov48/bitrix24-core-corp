<?php
return [
	'controllers' => [
		'value' => [
			'defaultNamespace' => '\\Bitrix\\Mobile\\Controller',
			'restIntegration' => [
				'enabled' => true,
			],
		],
		'readonly' => true,
	],
	'feature-flags' => [
		'value' => [
			\Bitrix\Mobile\Feature\SupportFeature::class,
			\Bitrix\Mobile\Feature\WhatsNewFeature::class,
			\Bitrix\Mobile\Feature\MenuFeature::class,
		],
		'readonly' => true,
	],
];
