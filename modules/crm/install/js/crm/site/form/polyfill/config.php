<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'lib/assign.js',
		'lib/objectkeys.js',
		'lib/fetch.js',
		'lib/customevent.js',
		'lib/formdata/formdata.js',
	],

	'rel' => [
		'main.polyfill.find',
		'main.polyfill.includes',
		'main.polyfill.promise',
	],

	'bundle_js' => 'main_polyfill_includes'
];