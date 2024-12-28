<?php

namespace Bitrix\HumanResources\Contract\Repository\HcmLink;

use Bitrix\HumanResources\Item\Collection\HcmLink\CompanyCollection;
use Bitrix\HumanResources\Item\HcmLink\Company;

interface CompanyRepository
{
	public function getById(int $id): ?Company;

	public function getByUnique(string $code): ?Company;

	public function getByCompanyId(int $myCompanyId): CompanyCollection;

	public function save(Company $item): Company;

	public function add(Company $item): Company;

	public function update(Company $item): Company;

	public function delete(int $id): void;

	public function getList(?int $limit = null, int $offset = 0): CompanyCollection;

	/**
	 * @param list<int> $ids
	 *
	 * @return CompanyCollection
	 */
	public function getListByIds(array $ids, ?int $limit = null, int $offset = 0): CompanyCollection;
}