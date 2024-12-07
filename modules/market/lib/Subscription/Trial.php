<?php

namespace Bitrix\Market\Subscription;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Market\Subscription;
use Bitrix\Rest\Marketplace\Client;
use Bitrix\Rest\OAuthService;
use CRestUtil;

class Trial
{
	public static function isAvailable(): bool
	{
		if (!Loader::includeModule('rest')) {
			return false;
		}

		return Client::isSubscriptionDemoAvailable() && Subscription::getFinishDate() === null;
	}

	public static function activate(): array
	{
		if (!Trial::isAvailable()) {
			return [
				'error' => Loc::getMessage('MARKET_ACTIVATE_DEMO_NOT_AVAILABLE'),
			];
		}

		if (!Loader::includeModule('rest')) {
			return [
				'error' => Loc::getMessage('MARKET_ACTIVATE_DEMO_ACCESS_DENIED'),
			];
		}

		if (!OAuthService::getEngine()->isRegistered()) {
			try {
				OAuthService::register();
			} catch (SystemException $e) {
				return [
					'error' => Loc::getMessage('MARKET_CONFIG_ACTIVATE_ERROR'),
					'error_description' => $e->getMessage(),
					'error_code' => $e->getCode(),
				];
			}
		}

		try {
			OAuthService::getEngine()->getClient()->getApplicationList();
		} catch (SystemException $e) {
			return [
				'error' => Loc::getMessage('MARKET_CONFIG_ACTIVATE_ERROR'),
				'error_description' => $e->getMessage(),
				'error_code' => 4,
			];
		}


		if (!OAuthService::getEngine()->isRegistered()) {
			return [
				'error' => Loc::getMessage('MARKET_CONFIG_ACTIVATE_ERROR'),
				'error_code' => 1,
			];
		}

		$loadedBitrix24 = Loader::includeModule('bitrix24');
		$queryFields = $loadedBitrix24 ? Trial::getB24Fields() : Trial::getCPFields();

		if (empty($queryFields)) {
			return [];
		}

		$httpClient = new HttpClient();
		$response = $httpClient->post('https://www.1c-bitrix.ru/buy_tmp/b24_coupon.php', $queryFields);
		if (!$response) {
			return [];
		}

		$result = [
			'result' => true,
		];
		if (mb_strpos($response, 'OK') === false) {
			$result = [
				'error' => Loc::getMessage('MARKET_CONFIG_ACTIVATE_ERROR'),
				'error_code' => 2,
			];
		}

		if (!$loadedBitrix24) {
			require_once($_SERVER['DOCUMENT_ROOT']
				. '/bitrix/modules/main/classes/general/update_client.php');
			$errorMessage = '';
			\CUpdateClient::GetUpdatesList($errorMessage, LANG);
		}

		return $result;
	}

	private static function getB24Fields(): array
	{
		$server = Context::getCurrent()->getServer();

		$queryFields = [
			'DEMO' => 'subscription',
			'SITE' => (defined('BX24_HOST_NAME')) ? BX24_HOST_NAME : $server->getHttpHost(),
		];

		if (function_exists('bx_sign')) {
			$queryFields['hash'] = bx_sign(md5(implode('|', $queryFields)));
		}

		return $queryFields;
	}

	private static function getCPFields(): array
	{
		$queryFields = [];

		$LICENSE_KEY = false;
		include $_SERVER['DOCUMENT_ROOT'] . '/bitrix/license_key.php';

		if (!empty($LICENSE_KEY)) {
			$queryFields = [
				'DEMO' => 'subscription',
				'SITE' => 'cp',
				'key' => md5($LICENSE_KEY),
				'hash' => md5('cp' . '|' . 'subscription' . '|' . md5($LICENSE_KEY)),
			];
		}

		return $queryFields;
	}
}