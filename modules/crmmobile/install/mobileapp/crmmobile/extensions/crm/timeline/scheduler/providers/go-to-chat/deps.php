<?php

return [
	'extensions' => [
		'crm:loc',
		'crm:type',
		'crm:communication/communication-selector',
		'crm:multi-field-drawer',
		'imconnector:connectors/telegram',
		'imconnector:consents/notification-service',
		'layout/ui/warning-block',
		'alert',
		'haptics',
		'type',
		'notify-manager',
		'utils/color',
		'utils/error-notifier',
		'utils/skeleton',
	],
	'bundle' => [
		'./src/messenger-slider',
		'./src/settings-block',
		'./src/clients-selector',
		'./src/providers-selector',
	],
];
