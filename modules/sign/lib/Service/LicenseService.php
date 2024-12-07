<?php

namespace Bitrix\Sign\Service;

use Bitrix\Main;
use Bitrix\Sign\Result\Service\License\LoadTariffResult;
use Bitrix\Sign\Contract;

class LicenseService
{
	private const LOAD_TIMEOUT = 15;
	private const CACHE_TTL = 3600;

	private const CACHE_TARIFF_KEY = 'sign_license_tariff';

	public function __construct(
		private readonly Contract\Util\Cache $cache,
	)
	{
		$this->cache->setTtl(self::CACHE_TTL);
	}

	public function loadTariff(): Main\Result | LoadTariffResult
	{
		$cachedTariff = $this->cache->get(self::CACHE_TARIFF_KEY);
		if ($cachedTariff)
		{
			return new LoadTariffResult($cachedTariff);
		}

		$endpoint = Main\Application::getInstance()->getLicense()->getDomainStoreLicense() . '/verify.php';

		$httpClient = (new Main\Web\HttpClient())->setTimeout(self::LOAD_TIMEOUT);
		$httpClient->post($endpoint, $this->makeLicenseData());

		try
		{
			$data = Main\Web\Json::decode($httpClient->getResult());
		}
		catch (Main\ArgumentException $e)
		{
			return (new Main\Result())->addError(new Main\Error('Incorrect response data'));
		}

		$tariffCode = ($data['result'] ?? [])['TARIF'] ?? null;
		if (empty($tariffCode))
		{
			return (new Main\Result())->addError(new Main\Error('Empty tariff data'));
		}

		try
		{
			$this->cache->set(self::CACHE_TARIFF_KEY, $tariffCode);
		}
		catch (Main\ArgumentException $exception) {}

		return new LoadTariffResult($tariffCode);
	}

	private function makeLicenseData(): array
	{
		$licenseData = [
			'BX_TYPE' => Main\Service\MicroService\Client::getPortalType(),
			'BX_LICENCE' => Main\Service\MicroService\Client::getLicenseCode(),
			'SERVER_NAME' => Main\Service\MicroService\Client::getServerName(),
		];
		$licenseData['BX_HASH'] = Main\Service\MicroService\Client::signRequest($licenseData);

		return $licenseData;
	}
}