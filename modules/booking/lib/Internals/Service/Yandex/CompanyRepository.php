<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex;

use Bitrix\Booking\Internals\Service\Yandex\Dto\Item\Company;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Config\Option;

class CompanyRepository
{
	private const OPTION_NAME = 'yandex_company';

	public function getDefaultCompany(): Company|null
	{
		return $this->getById(Company::DEFAULT_COMPANY_ID);
	}

	public function getById(string $companyId): Company|null
	{
		if ($companyId !== Company::DEFAULT_COMPANY_ID)
		{
			return null;
		}

		$company = (new Company())->setId($companyId);

		$value = $this->getValue();

		if (isset($value['timezone']))
		{
			$company->setTimezone((string)$value['timezone']);
		}

		if (isset($value['permalink']))
		{
			$company->setPermalink((string)$value['permalink']);
		}

		return $company;
	}

	public function save(Company $company): void
	{
		$this->saveValue([
			'name' => $company->getName(),
			'permalink' => $company->getPermalink(),
			'timezone' => $company->getTimezone(),
			'rubrics' => $company->getRubrics(),
		]);
	}

	private function getValue(): array
	{
		$value = Option::get('booking', self::OPTION_NAME);
		if ($value === '')
		{
			return [];
		}

		try
		{
			$value = Json::decode($value);

			return is_array($value) ? $value : [];
		}
		catch (SystemException) {}

		return [];
	}

	private function saveValue(array $value): void
	{
		Option::set(
			'booking',
			self::OPTION_NAME,
			JSON::encode($value),
		);
	}
}
