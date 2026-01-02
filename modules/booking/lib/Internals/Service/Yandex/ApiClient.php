<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex;

use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Config\Option;
use Bitrix\Main\License\UrlProvider;
use Bitrix\Main\Result;
use Bitrix\Main\Service\MicroService\BaseSender;

class ApiClient extends BaseSender
{
	private const CUSTOM_SERVICE_URL_OPTION = 'yandex_service_url';

	public function saveCompaniesFeed(string $companyFeed): Result
	{
		return $this->performRequest(
			'bookingservice.api_v1.Yandex.Portal.CompanyFeed.saveCompanies',
			[
				'companies' => $companyFeed,
			]
		);
	}

	public function register(): Result
	{
		return $this->performRequest('bookingservice.api_v1.Yandex.Portal.Account.register');
	}

	public function unregister(): Result
	{
		return $this->performRequest('bookingservice.api_v1.Yandex.Portal.Account.unregister');
	}

	public function setCustomServiceUrl(string $url): void
	{
		Option::set('booking', self::CUSTOM_SERVICE_URL_OPTION, $url);
	}

	protected function getServiceUrl(): string
	{
		if (defined('BOOKING_SERVICE_URL'))
		{
			return BOOKING_SERVICE_URL;
		}

		$customServiceUrl = Option::get('booking', self::CUSTOM_SERVICE_URL_OPTION);
		if (!empty($customServiceUrl))
		{
			return $customServiceUrl;
		}

		return 'https://bookservice-ru.' . (new UrlProvider())->getTechDomain() . '/';
	}

	protected function getClientServerName(): string
	{
		$publicUrl = Configuration::getInstance()->get('booking')['public_url'] ?? '';
		if (!empty($publicUrl))
		{
			return $publicUrl;
		}

		return parent::getClientServerName();
	}
}
