<?php

use \Bitrix\Location\Service;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => './dist/widget.bundle.js',
	'css' => './dist/widget.bundle.css',
	'lang' => BX_ROOT.'/modules/location/js/widget.php',
	'rel' => [
		'main.core',
		'main.popup',
		'main.core.events',
		'ui.entity-editor',
		'ui.common',
		'ui.viewer',
		'location.core',
		'location.google',
		'location.osm'
	],
	'skip_core' => false,
	'oninit' => static function()
	{
		if(!\Bitrix\Main\Loader::includeModule('location'))
		{
			throw new \Bitrix\Main\SystemException('Module Location have not been installed');
		}

		$sourceCode = '';
		$sourceParams = [];
		$sourceLanguageId = LANGUAGE_ID;

		if($source = Service\SourceService::getInstance()->getSource())
		{
			if(!Service\AddressService::getInstance()->isLimitReached())
			{
				$sourceCode = $source->getCode();
				$sourceParams = $source->getJSParams();
				$sourceLanguageId = $source->convertLang(LANGUAGE_ID);
			}
		}

		$format = Service\FormatService::getInstance()->findDefault(LANGUAGE_ID);
		$format  = $format ? $format->toJson() : '';

		/*
		 * Here it could be a performance problem due to custom handlers.
		 * Make an option, or receive via ajax request.
		 */
		$userLocation = \Bitrix\Location\Infrastructure\UserLocation::findUserLocation();

		return [
			'lang_additional' => [
				'LOCATION_WIDGET_SOURCE_CODE' => $sourceCode,
				'LOCATION_WIDGET_SOURCE_PARAMS' => $sourceParams,
				'LOCATION_WIDGET_DEFAULT_FORMAT' => $format,
				'LOCATION_WIDGET_LANGUAGE_ID' => LANGUAGE_ID,
				'LOCATION_WIDGET_SOURCE_LANGUAGE_ID' => $sourceLanguageId,
				'LOCATION_WIDGET_USER_LOCATION' => $userLocation->toJson()
			]
		];
	}
];