<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Crm;

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
			"properties" => [
				"analytics" => Main\Loader::includeModule('crm')
					? Crm\WebForm\Helper::getExternalAnalyticsData(null, true)
					: [],
				"recaptcha" => [
					'key' => Main\Loader::includeModule('crm')
						? (Crm\WebForm\ReCaptcha::getKey(2) ?: Crm\WebForm\ReCaptcha::getDefaultKey(2))
						: null
				],
				"resourcebooking" => [
					'link' => Main\Loader::includeModule('crm')
						? Crm\UI\Webpack\Form\ResourceBooking::instance()->getEmbeddedFileUrl()
						: null
				],
			]
		]
	]
];