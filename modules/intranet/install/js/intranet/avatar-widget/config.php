<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die;
}

return [
	'css' => 'dist/avatar-widget.bundle.css',
	'js' => 'dist/avatar-widget.bundle.js',
	'rel' => [
		'ui.popupcomponentsmaker',
		'ui.avatar',
		'timeman.work-status-control-panel',
		'ui.analytics',
		'ui.info-helper',
		'crm.router',
		'pull.client',
		'humanresources.hcmlink.salary-vacation-menu',
		'im.v2.lib.desktop-api',
		'ui.cnt',
		'intranet.desktop-account-list',
		'main.popup',
		'ui.short-qr-auth',
		'main.core',
		'ui.buttons',
		'main.sidepanel',
		'main.core.events',
	],
	'skip_core' => false,
];
