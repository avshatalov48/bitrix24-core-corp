<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/document-validation.bundle.css',
	'js' => 'dist/document-validation.bundle.js',
	'rel' => [
		'main.core',
		'sign.v2.b2e.representative-selector',
		'sign.type',
		'sign.v2.helper',
	],
	'skip_core' => false,
	'settings' => [
		'currentUserId' => \Bitrix\Main\Engine\CurrentUser::get()->getId(),
	]
];
