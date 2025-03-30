<?php

namespace Bitrix\HumanResources\Contract\Repository\HcmLink;

use Bitrix\HumanResources\Item\Collection\HcmLink\PersonCollection;
use Bitrix\HumanResources\Item\HcmLink\Person;
use Bitrix\HumanResources\Result\SuccessResult;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

interface PersonRepository
{
	public function add(Person $person): Person;

	public function update(Person $person): Person;

	public function getByCompany(int $companyId): PersonCollection;

	public function getList(
		int $companyId,
		int $limit = 100,
		int $offset = 0,
		?DateTime $fromDate = null
	): PersonCollection;

	public function getMappedUserIdsByCompanyId(int $companyId): array;

	public function getByIdsExcludeMapped(array $ids, int $companyId): PersonCollection;

	public function getListByIds(array $ids): PersonCollection;

	public function getByCompanyExcludeMapped(int $companyId, ?int $limit): PersonCollection;

	public function getMappedPersons(ConditionTree $filter, int $limit, int $offset): PersonCollection;

	public function getMappedPersonsByCompanyId(int $companyId, ConditionTree $filter, int $limit, int $offset): PersonCollection;

	public function countAllMappedByCompanyId(int $companyId): int;

	public function deleteLink(PersonCollection $personCollection): PersonCollection;

	public function getById(int $id): ?Person;

	public function getCountMappedPersonsByUserIds(int $companyId, array $userIds): int;

	public function getByUnique(int $companyId, string $code): ?Person;

	public function searchByIndexAndCompanyId(string $search, int $companyId, int $limit): PersonCollection;

	public function deleteSearchIndexByPersonId(int $personId): Result|SuccessResult;

	public function updateSearchIndexByPersonId(int $personId, string $searchContent): Result|SuccessResult;

	public function addSearchIndexByPersonId(int $personId, string $searchContent): Result|SuccessResult;

	public function hasPersonSearchIndex(int $personId): bool;

	public function countUnmappedPersons(int $companyId): int;

	public function getUnmappedPersonsByCompanyId(int $companyId, int $limit, ?string $searchName = null): PersonCollection;

	public function countNotMappedAndGroupByCompanyId(array $companyIds = []): array;

	public function existByUserId(int $userId): bool;

	public function getByUserIdsAndGroupByCompanyId(int $userId): array;

	public function updateCounter(int $personId): Result|SuccessResult;
}