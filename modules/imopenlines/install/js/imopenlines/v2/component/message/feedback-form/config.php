<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/feedback-form.bundle.css',
	'js' => 'dist/feedback-form.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'im.v2.component.message.base',
	],
	'skip_core' => true,
];
