<?

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/disk-rich-text-area.bundle.css',
	'js' => 'dist/disk-rich-text-area.bundle.js',
	'rel' => [
		'ui.rich-text-area',
		'disk.uploader.user-field-widget',
	],
	'skip_core' => false,
];
