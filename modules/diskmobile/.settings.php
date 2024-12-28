<?php

return [
	'controllers' => [
		'value' => [
			'defaultNamespace' => '\\Bitrix\\DiskMobile\\Controller',
			'restIntegration' => [
				'enabled' => false,
			],
		],
		'readonly' => true,
	],
	'feature-flags' => [
		'value' => [
			\Bitrix\DiskMobile\AirDiskFeature::class,
			\Bitrix\DiskMobile\LegacyUploaderFeature::class,
		],
		'readonly' => true,
	],
];
