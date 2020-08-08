<?php

$fileman_google_map_api_key = \Bitrix\Main\Config\Option::get('fileman', 'google_map_api_key', '');

$location_default_option = [
	'use_google_api' => 'Y',
	'google_map_api_key' => $fileman_google_map_api_key,
	'google_map_api_key_backend' => $fileman_google_map_api_key,
	'address_format_code' => getDefaultAddressFormatCode()
];

/**
 * Copy & paste from \Bitrix\Location\Infrastructure\FormatCode::getDefault()
 * The reason is cycling during the module installation
 * @param mixed|string $languageId
 * @return string
 */
function getDefaultAddressFormatCode(string $languageId = LANGUAGE_ID): string
{
	switch ($languageId)
	{
		case 'kz':
			$result = 'RU_2';
			break;

		case 'de':
			$result = 'EU';
			break;

		case 'en':
			$result = 'US';
			break;

		//case 'ru':
		//case 'by':
		//case 'ua':
		default:
			$result = 'RU';
	}

	return $result;
}