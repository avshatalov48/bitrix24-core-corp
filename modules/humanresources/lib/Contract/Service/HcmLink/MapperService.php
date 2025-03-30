<?php

namespace Bitrix\HumanResources\Contract\Service\HcmLink;

use Bitrix\HumanResources\Item\Collection\HcmLink\PersonCollection;
use Bitrix\HumanResources\Result\Service\HcmLink\FilterNotMappedUserIdsResult;
use Bitrix\HumanResources\Result\Service\HcmLink\GetMappingEntityCollectionResult;
use Bitrix\HumanResources\Result\Service\HcmLink\GetMatchesForMappingResult;
use Bitrix\HumanResources\Result\Service\HcmLink\GetMultipleVacancyEmployeesResult;
use Bitrix\Main;

interface MapperService
{
	public function filterNotMappedUserIds(int $companyId, int ...$userIds): Main\Result | FilterNotMappedUserIdsResult;

	public function getMappingEntitiesForUnmappedPersons(PersonCollection $personCollection): GetMappingEntityCollectionResult;

	public function listMappedUserIdWithOneEmployeePosition(int $companyId, int ...$userIds): array;

	public function getSuggestForUsers(int $companyId, array $users): GetMatchesForMappingResult;

	public function getSuggestForPeople(array $people, array $excludeIds): GetMatchesForMappingResult;

	public function getEmployeesWithMultipleVacancy(int $hcmLinkCompanyId, int ...$userIds): Main\Result | GetMultipleVacancyEmployeesResult;
}