<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$inviteWidgetLink = CIntranetInviteDialog::showInviteDialogLink(
	[
		'analyticsLabel' => [
			'analyticsLabel[source]' => 'hint_invite',
		]
	]
);

return [
	'css' => 'dist/invitation-notification.bundle.css',
	'js' => 'dist/invitation-notification.bundle.js',
	'rel' => [
		'main.core',
		'ui.banner-dispatcher',
		'main.popup',
		'ui.buttons',
		'ui.analytics',
	],
	'settings' => [
		'inviteWidgetLink' => $inviteWidgetLink
	],
	'skip_core' => false,
];
