<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'script.js',
	'rel' => [
		//'ui.webpacker'
	],
	"options" => [
		"webpacker" => [
			"callMethod" => "window.b24form.Loader.run",
		]
	]
];