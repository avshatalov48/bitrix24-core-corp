<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/call.bundle.css',
	'js' => 'dist/call.bundle.js',
	'rel' => [
		'ui.vue3',
		'crm.ai.slider',
		'crm.ai.textbox',
		'ui.notification',
		'crm.audio-player',
		'pull.client',
		'pull.queuemanager',
		'ui.lottie',
		'crm.copilot.call-assessment-selector',
		'crm.router',
		'crm.timeline.tools',
		'main.core.events',
		'ui.bbcode.formatter.html-formatter',
		'ui.sidepanel',
		'ui.design-tokens',
		'main.core',
	],
	'skip_core' => false,
];
