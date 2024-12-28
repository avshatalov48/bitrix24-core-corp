<?php

namespace Bitrix\HumanResources\Service\HcmLink\Counter;

use Bitrix\HumanResources\Contract\Repository\HcmLink\PersonRepository;
use Bitrix\HumanResources\Service\Container;
use CGlobalCounter;

class CompanyCounterService
{
	private const CODE = 'hr_hcmlink_not_mapped_companies';

	private PersonRepository $personRepository;

	public function __construct(
		?PersonRepository $personRepository = null
	)
	{
		$this->personRepository = $personRepository ?? Container::getHcmLinkPersonRepository();
	}

	public function getCounterId(): string
	{
		return self::CODE;
	}

	public function get(): int
	{
		return (int)CGlobalCounter::GetValue(self::CODE, '**');
	}

	public function set(int $value): void
	{
		CGlobalCounter::Set(self::CODE, $value, '**');
	}

	public function update(): void
	{
		$result = $this->personRepository->countNotMappedAndGroupByCompanyId();
		$notMappedCompaniesCount = count($result);

		$this->set($notMappedCompaniesCount);
	}

	public function clear(): void
	{
		CGlobalCounter::Clear(self::CODE, '**');
	}
}