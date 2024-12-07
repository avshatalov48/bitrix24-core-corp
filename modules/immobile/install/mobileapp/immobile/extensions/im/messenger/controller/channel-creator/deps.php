<?php

return [
	'extensions' => [
		'loc',
		'apptheme',
		'type',
		'files/entry',
		'files/converter',
		'im:lib/theme',
		'im:messenger/assets/common',
		'im:messenger/const',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/rest',
		'im:messenger/lib/emitter',
		'im:messenger/lib/params',
		'im:messenger/controller/search',
		'im:messenger/lib/ui/selector',
		'im:messenger/lib/ui/base/checkbox',
	],
	'bundle' => [
		'./src/components/avatar-button',
		'./src/components/title-field',
		'./src/components/description-field',
		'./src/components/clear-text-button',
		'./src/components/privacy-selector',
		'./src/step/base',
		'./src/step/add-subscribers',
		'./src/step/enter-name',
		'./src/step/settings',
		'./src/creator',
		
	],
];