<?php

namespace Bitrix\Location\Source\Google;

use Bitrix\Location\Source\BaseSource;
use \Bitrix\Location\Common\CachedPool;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;

final class GoogleSource extends BaseSource
{
	protected $code = 'GOOGLE';
	protected $apiKey = '';

	/**
	 * GoogleSource constructor.
	 * @param HttpClient $httpClient
	 * @param CachedPool|null $cachePool
	 */
	public function __construct(HttpClient $httpClient, CachedPool $cachePool = null)
	{
		$apiKey = static::findApiKey();
		$apiKeyBack = static::findApiKeyBackend();
		$this->apiKey = $apiKey;
		$this->repository = new Repository($apiKeyBack, $httpClient, $this, $cachePool);
	}

	/**
	 * @return string|null
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function findApiKey(): ?string
	{
		$result = '';

		if($apiKey = Option::get('location', 'google_map_api_key', ''))
		{
			$result = $apiKey;
		}
		else if(Loader::includeModule('fileman'))
		{
			$result = \Bitrix\Fileman\UserField\Types\AddressType::getApiKey();
		}

		return $result;
	}

	public static function findApiKeyBackend(): ?string
	{
		return Option::get('location', 'google_map_api_key_backend', '');
	}

	public function getJSParams(): array
	{
		return [
			'apiKey' => $this->apiKey,
			'showPhotos' => Option::get('location', 'google_map_show_photos', 'N') === 'Y',
			'useGeocodingService' => Option::get('location', 'google_use_geocoding_service', 'N') === 'Y',
		];
	}

	/** @inheritDoc */
	public function convertLang(string $bitrixLang): string
	{
		// https://developers.google.com/maps/faq#languagesupport

		$langMap = [
			'br' => 'pt-BR',	// Portuguese (Brazil)
			'la' => 'es', 		// Spanish
			'sc' => 'zh-CN', 	// Chinese (Simplified)
			'tc' => 'zh-TW', 	// Chinese (Traditional)
			'vn' => 'vi' 		// Vietnamese
		];

		return $langMap[$bitrixLang] ?? $bitrixLang;
	}
}
