<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex;

use Bitrix\Booking\Internals\Service\Yandex\Dto\Collection\CompanyCollection;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;

class CompanyFeedSender
{
	private const HASH_OPTION = 'yandex_company_feed_hash';

	private ApiClient $apiClient;

	public function __construct(ApiClient $apiClient)
	{
		$this->apiClient = $apiClient;
	}

	public function sendFeed(CompanyCollection $companyCollection): Result
	{
		$result = new Result();

		$validateResult = $companyCollection->validate();
		if (!$validateResult->isSuccess())
		{
			$result->addErrors($validateResult->getErrors());

			return $result;
		}

		$rawCompanyFeed = $this->getRawFeed($companyCollection);

		$result = $this->apiClient->saveCompaniesFeed($rawCompanyFeed);
		if ($result->isSuccess())
		{
			Option::set('booking', self::HASH_OPTION, $this->getRawFeedHash($rawCompanyFeed));
		}

		return $result;
	}

	public function getFeedHash(CompanyCollection $companyCollection): string
	{
		return $this->getRawFeedHash(
			$this->getRawFeed($companyCollection)
		);
	}

	public function getLastFeedHash(): string
	{
		return Option::get('booking', self::HASH_OPTION);
	}

	private function getRawFeed(CompanyCollection $companyCollection): string
	{
		return Json::encode($companyCollection->toArray());
	}

	private function getRawFeedHash(string $companyFeed): string
	{
		return hash('sha256', $companyFeed);
	}
}
