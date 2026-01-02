<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex;

use Bitrix\Booking\Internals\Service\Yandex\Dto\Collection\CompanyCollection;
use Bitrix\Booking\Internals\Integration;

class CompanyFeedProvider
{
	public function __construct(
		private readonly CompanyRepository $companyRepository
	)
	{
	}

	public function getCompanies(): CompanyCollection
	{
		$result = new CompanyCollection();

		$defaultCompany = $this->companyRepository->getDefaultCompany();
		if ($defaultCompany)
		{
			$result->add($defaultCompany);
		}

		return $result;
	}
}
