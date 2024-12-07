<?php

return [
	'extensions' => [
		'crm:loc',
		'crm:type',
		'crm:communication/communication-selector',
		'crm:message-senders-connector',
		'crm:multi-field-drawer',

		'imconnector:connectors/telegram',
		'imconnector:consents/notification-service',
		'layout/ui/warning-block',
		'alert',
		'haptics',
		'apptheme',
		'type',
		'assets/icons',
		'notify-manager',
		'utils/color',
		'utils/error-notifier',
		'utils/skeleton',
		'layout/ui/context-menu',
	],
	'bundle' => [
		'./src/messenger-slider',
		'./src/settings-block',
		'./src/clients-selector',
		'./src/providers-selector',
	],
];
