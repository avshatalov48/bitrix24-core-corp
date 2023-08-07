<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\UI;
use Bitrix\Crm;

$fontProxy = [];
if (Main\Loader::includeModule('ui') && class_exists('\Bitrix\UI\Fonts\Proxy'))
{
	foreach (UI\Fonts\Proxy::getMap() as $sourceDomain => $targetDomain)
	{
		$fontProxy[] = [
			'source' => $sourceDomain,
			'target' => $targetDomain,
		];
	}
}

$region = Main\Application::getInstance()->getLicense()->getRegion();

$uploaderSettings = [];
if (
	Main\Loader::includeModule('crm')
	&& class_exists('\Bitrix\Crm\FileUploader\SiteFormFileUploaderController')
)
{
	$uploaderSettings = Crm\FileUploader\SiteFormFileUploaderController::getSettings();
}

return [
	'js' => [
		'crm.site.form.js',
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
				"isResourcesMinified" => Main\IO\File::isFileExists(__DIR__ . '/dist/app.bundle.min.js'),
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
				"proxy" => [
					"fonts" => $fontProxy,
				],
				"abuse" => [
					"link" => (Main\Loader::includeModule('bitrix24')
							&& method_exists(\Bitrix\Bitrix24\Form\AbuseZoneMap::class, 'getLink'))
							? \Bitrix\Bitrix24\Form\AbuseZoneMap::getLink($region)
							: '',
				],
				"uploader" => $uploaderSettings
			]
		]
	]
];