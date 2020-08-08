<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(
	"css" => "dist/address.bundle.css",
	"js" => "dist/address.bundle.js",
	'rel' => [
	],
	'skip_core' => true,
	'oninit' => function() {
		if (\Bitrix\Main\Loader::includeModule('location'))
		{
			return [
				'rel' => [
					'location.core',
					'location.widget',
				],
			];
		}
	}
);