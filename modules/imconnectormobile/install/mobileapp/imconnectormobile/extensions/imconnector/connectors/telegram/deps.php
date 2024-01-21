<?php

return [
	'extensions' => [
		'loc',
		'type',
		'alert',
		'qrauth',
		'apptheme',
		'utils/object',
		'notify-manager',
		'in-app-url',
		'utils/color',
		'layout/ui/fields/user',
		'imconnector:lib/ui/banner',
		'imconnector:lib/ui/button-switcher',
		'imconnector:lib/ui/buttons/complete',
		'imconnector:lib/ui/buttons/link',
		'imconnector:lib/ui/buttons/copy',
		'imconnector:lib/ui/buttons/qr',
		'imconnector:lib/ui/setting-step',
		'imconnector:lib/rest-manager/telegram',
	],
	'bundle' => [
		'./src/view/registry',
		'./src/view/edit',
		'./src/controllers/registrar',
		'./src/controllers/editor',
		'./src/layout-components/token-input',
		'./src/layout-components/queue-field',
		'./src/layout-components/loader',
		'./src/layout-components/registry-complete',
	],
];