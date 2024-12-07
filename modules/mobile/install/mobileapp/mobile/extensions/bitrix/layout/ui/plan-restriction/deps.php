<?php
return [
	'components' => [
		'tariff-plan-restriction',
	],
	'extensions' => [
		'analytics',
		'helpers/component',
		'layout/ui/loading-screen',
		'loc',
		'qrauth/utils',
		'require-lazy',
		'rest/run-action-executor',
		'storage-cache',
		'tokens',
		'type',
		'ui-system/blocks/status-block',
		'ui-system/form/buttons',
		'ui-system/layout/box',
		'ui-system/layout/dialog-footer',
	],
	'bundle' => [
		'./src/provider',
	],
];
