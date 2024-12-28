<?php
return [
	'components' => [
		'tariff-plan-restriction',
	],
	'extensions' => [
		'loc',
		'analytics',
		'utils/page-manager',
		'helpers/component',
		'layout/ui/loading-screen',
		'qrauth/utils',
		'require-lazy',
		'rest/run-action-executor',
		'storage-cache',
		'tokens',
		'type',
		'bottom-sheet',
		'ui-system/blocks/status-block',
		'ui-system/form/buttons',
		'ui-system/layout/box',
		'ui-system/layout/dialog-footer',
	],
	'bundle' => [
		'./src/provider',
	],
];
