<?php

return [
	'extensions' => [
		'rest',
		'loc',
		'alert',
		'notify',
		'apptheme',
		'utils/date',
		'utils/url',
		'utils/color',
		'qrauth/utils',
		'pull/client/events',
		'layout/ui/context-menu',
		'layout/ui/loaders/bitrix-cloud',
		'animation/components/fade-view',
		'animation',
		'crm:document/edit',
		'crm:document/qr-code',
		'crm:document/context-menu',
		'crm:document/share-dialog',
		'crm:document/shared-utils',
		'crm:error',
	],
	'bundle' => [
		'./src/download-link',
		'./src/loading-screen',
		'./src/error-panel',
		'./src/bottom-toolbar',
		'./src/pdf-view',
	],
];
