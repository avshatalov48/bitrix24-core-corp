<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/index.bundle.css',
	'js' => 'dist/index.bundle.js',
	'rel' => [
		'ui.notification',
		'ui.dialogs.messagebox',
		'crm.activity.file-uploader-popup',
		'crm.activity.settings-popup',
		'crm.activity.todo-editor',
		'crm.timeline.tools',
		'crm.datetime',
		'main.popup',
		'main.core.events',
		'ui.vue3',
		'main.core',
		'crm.router',
		'ui.cnt',
		'ui.label',
		'ui.buttons',
		'ui.vue3.components.audioplayer',
		'ui.vue3.components.hint',
		'ui.alerts',
		'ui.fonts.opensans',
		'loader',
		'crm.timeline.editors.comment-editor',
		'calendar.sharing.interface',
	],
	'skip_core' => false,
];
