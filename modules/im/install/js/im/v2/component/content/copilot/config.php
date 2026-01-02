<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/copilot-content.bundle.css',
	'js' => 'dist/copilot-content.bundle.js',
	'rel' => [
		'im.v2.lib.logger',
		'main.core',
		'main.core.events',
		'im.v2.provider.service.chat',
		'im.v2.component.elements.chat-title',
		'im.v2.component.elements.avatar',
		'im.v2.component.content.elements',
		'im.v2.component.entity-selector',
		'im.v2.lib.promo',
		'im.v2.lib.analytics',
		'main.popup',
		'im.v2.component.elements.popup',
		'im.v2.component.textarea',
		'im.public',
		'im.v2.component.elements.copilot-roles-dialog',
		'im.v2.component.elements.button',
		'im.v2.const',
		'im.v2.lib.theme',
		'im.v2.provider.service.copilot',
	],
	'skip_core' => false,
];