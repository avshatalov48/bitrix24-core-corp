<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/kanban-entity-footer.bundle.css',
	'js' => 'dist/kanban-entity-footer.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'sign.v2.b2e.sign-cancellation',
		'sign.v2.b2e.sign-link',
		'crm.router',
	],
	'skip_core' => false,
];