<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/index.bundle.css',
	'js' => 'dist/index.bundle.js',
	'rel' => [
		'ui.dialogs.messagebox',
		'main.popup',
		'ui.progressround',
		'main.loader',
		'date',
		'sign.ui',
		'color_picker',
		'ui.stamp.uploader',
		'sign.backend',
		'crm.form.fields.selector',
		'crm.requisite.fieldset-viewer',
		'sign.document',
		'main.core',
		'ui.draganddrop.draggable',
		'main.core.events',
		'ui.buttons',
		'sign.tour',
		'ui.info-helper',
		'spotlight',
	],
	'skip_core' => false,
];
