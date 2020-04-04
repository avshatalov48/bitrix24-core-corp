<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'dist/app.bundle.js',
	],
	'skip_core' => true,
	"lang" => [
		"/bitrix/modules/crm/install/js/site/form.php",
		"/bitrix/modules/crm/install/js/site/field.php",
	],
	"options" => [
		"webpacker" => [
			"useAllLangs" => true,
			"useLangCamelCase" => true,
			"deleteLangPrefixes" => ["CRM_SITE_FORM_"],
		]
	]
];