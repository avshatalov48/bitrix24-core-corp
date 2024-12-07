<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/editor.bundle.css',
	'js' => 'dist/editor.bundle.js',
	'rel' => [
		'sign.v2.helper',
		'main.popup',
		'sign.tour',
		'spotlight',
		'ui.buttons',
		'ui.dialogs.messagebox',
		'ui.info-helper',
		'sign.backend',
		'date',
		'ui.notification',
		'ui.stamp.uploader',
		'crm.form.fields.selector',
		'crm.requisite.fieldset-viewer',
		'sign.v2.b2e.field-selector',
		'sign.ui',
		'color_picker',
		'sign.document',
		'main.core',
		'ui.draganddrop.draggable',
		'main.core.events',
		'sign.v2.api',
	],
	'skip_core' => false,
];