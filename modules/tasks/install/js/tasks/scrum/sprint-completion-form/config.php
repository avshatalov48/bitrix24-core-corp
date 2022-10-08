<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/sprint.completion.form.bundle.css',
	'js' => 'dist/sprint.completion.form.bundle.js',
	'rel' => [
		'main.core.events',
		'ui.sidepanel.layout',
		'ui.confetti',
		'main.core',
		'ui.dialogs.messagebox',
		'ui.design-tokens',
		'ui.fonts.opensans',
		'ui.hint',
	],
	'skip_core' => false,
];