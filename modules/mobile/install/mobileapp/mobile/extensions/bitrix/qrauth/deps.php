<?php
return [
	'components' => [
		'qrcodeauth',
	],
	"extensions" => [
		'analytics',
		'loc',
		'rest',
		'notify',
		'tokens',
		'apptheme',
		'qrauth/utils',
		'bottom-sheet',
		'ui-system/typography',
		'ui-system/layout/box',
		'ui-system/layout/card',
		'ui-system/blocks/icon',
		'ui-system/blocks/link',
		'ui-system/layout/area',
		'ui-system/layout/area-list',
		'layout/ui/buttons/action',
		'layout/ui/buttons/cancel',
		'layout/ui/buttons/primary',
		'ui-system/form/buttons/button',
	],
	"bundle" => [
		"./src/auth",
		"./src/scanner",

	]
];
