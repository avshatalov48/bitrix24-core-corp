<?php

namespace Bitrix\Market\Rest;

use Bitrix\Landing\Manager;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Rest\Marketplace\Client;
use CBitrix24;

if (!defined('REST_MARKETPLACE_URL'))
{
	define('REST_MARKETPLACE_URL', '');
}

class Transport
{
	private const VERSION = 1;
	private const API_VERSION = 4;

	private string $serviceDomain;

	private const SERVICE_DOMAIN_LIST = [
		'en' => 'https://util.bitrixsoft.com',
		'ru' => 'https://util.1c-bitrix.ru',
		'kz' => 'https://util.1c-bitrix.kz',
		'by' => 'https://util.1c-bitrix.by',
		'ua' => 'https://util.bitrix.ua',
	];

	private static ?Transport $instance = null;

	public static function instance(): Transport
	{
		if (static::$instance == null) {
			static::$instance = new Transport();
		}

		return static::$instance;
	}

	public function __construct()
	{
		if (Loader::includeModule('bitrix24')){
			$region = CBitrix24::getLicensePrefix();
		} else {
			$region = Option::get('main', '~PARAM_CLIENT_LANG', LANGUAGE_ID);
		}

		$this->serviceDomain = self::SERVICE_DOMAIN_LIST[$region] ?? self::SERVICE_DOMAIN_LIST['en'];
	}

	public function call($method, $fields = []): array
	{
		$query = $this->prepareQuery($method, $fields);

		$response = $this->getHttpClient()->post($this->getServiceUrl(), $query);

		return $this->prepareAnswer($response);
	}

	public function batch($actions): array
	{
		$query = [];

		foreach ($actions as $key => $batch) {
			if (!isset($batch[1])){
				$batch[1] = [];
			}

			$query[$key] = $this->prepareQuery($batch[0], $batch[1]);
		}

		$response = $this->getHttpClient()->post($this->getServiceUrl(), ['batch' => $query]);

		return $this->prepareAnswer($response);
	}

	private function getHttpClient(): HttpClient
	{
		return new HttpClient([
			'socketTimeout' => 10,
			'streamTimeout' => 10,
		]);
	}

	private function getServiceUrl(): string
	{
		if (is_string(REST_MARKETPLACE_URL) && !empty(REST_MARKETPLACE_URL)){
			return REST_MARKETPLACE_URL;
		}

		return $this->serviceDomain . '/b24/apps.php';
	}

	private function prepareQuery($method, $fields)
	{
		if (!is_array($fields)) {
			$fields = [];
		}

		$fields['action'] = $method;
		$fields['apiVersion'] = self::API_VERSION;

		if ($this->isSubscriptionAccess()) {
			$fields['queryVersion'] = self::VERSION;
		}

		$fields['lang'] = LANGUAGE_ID;
		$fields['bsm'] = ModuleManager::isModuleInstalled('intranet') ? '0' : '1';

		if (Loader::includeModule('bitrix24') && defined('BX24_HOST_NAME')) {
			$fields['tariff'] = CBitrix24::getLicensePrefix();
			$fields['host_name'] = BX24_HOST_NAME;

			if (Loader::includeModule('landing')) {
				$fields['landing_copilot_available'] = Manager::getOption('landing_ai_sites_available', 'N');
			}
		} else {
			$fields['host_name'] = Context::getCurrent()->getRequest()->getHttpHost();

			@include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/license_key.php');
			$fields['license_key'] = ($LICENSE_KEY == 'DEMO') ? 'DEMO' : md5('BITRIX' . $LICENSE_KEY . 'LICENCE');
		}

		return $fields;
	}

	private function isSubscriptionAccess(): bool
	{
		if (!Loader::includeModule('rest')) {
			return false;
		}

		return Client::isSubscriptionAccess();
	}

	private function prepareAnswer($response): array
	{
		$responseData = [];

		if (!empty($response)) {
			try {
				$responseData = Json::decode($response);
			} catch (ArgumentException $e) {}
		}

		return is_array($responseData) ? $responseData : [];
	}
}
