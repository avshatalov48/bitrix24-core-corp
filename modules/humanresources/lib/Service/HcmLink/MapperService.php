<?php

namespace Bitrix\HumanResources\Service\HcmLink;

use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Item\Collection\HcmLink\EmployeeCollection;
use Bitrix\HumanResources\Item\Collection\HcmLink\MappingEntityCollection;
use Bitrix\HumanResources\Item\Collection\HcmLink\PersonCollection;
use Bitrix\HumanResources\Item\HcmLink\MappingEntity;
use Bitrix\HumanResources\Result\Service\HcmLink\FilterNotMappedUserIdsResult;
use Bitrix\HumanResources\Result\Service\HcmLink\GetMappingEntityCollectionResult;
use Bitrix\HumanResources\Result\Service\HcmLink\GetMatchesForMappingResult;
use Bitrix\HumanResources\Result\Service\HcmLink\GetMultipleVacancyEmployeesResult;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\HcmLink\EmployeeDataType;
use Bitrix\HumanResources\Util\PersonUtil;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class MapperService implements Contract\Service\HcmLink\MapperService
{
	public function __construct(
		private readonly Contract\Repository\HcmLink\CompanyRepository $companyRepository,
		private readonly Contract\Repository\HcmLink\PersonRepository $personRepository,
		private readonly Contract\Repository\HcmLink\EmployeeRepository $employeeRepository,
	) {}

	/**
	 * @param int $companyId
	 * @param list<int> $userIds
	 *
	 * @return Result|FilterNotMappedUserIdsResult
	 */
	public function filterNotMappedUserIds(int $companyId, int ...$userIds): Main\Result | FilterNotMappedUserIdsResult
	{
		$company = $this->companyRepository->getById($companyId);
		if (!$company)
		{
			return (new Main\Result())->addError(
				new Main\Error(
					Loc::getMessage('HUMANRESOURCES_HCMLINK_INTEGRATION_NOT_FOUND') ?? '',
					'HR_HCMLINK_INTEGRATION_NOT_FOUND',
				),
			);
		}

		$mappedUserIds = $this->personRepository->getMappedUserIdsByCompanyId($company->id);

		return new FilterNotMappedUserIdsResult(
			userIds: array_values(
				array_filter($userIds, fn(int $userId) => !in_array($userId, $mappedUserIds, true))
			)
		);
	}

	/**
	 * Get users who has only one employee
	 *
	 * @param int $companyId
	 * @param int ...$userIds
	 * @return array<int, int> userId => employeeId
	 */
	public function listMappedUserIdWithOneEmployeePosition(int $companyId, int ...$userIds): array
	{
		return $this->employeeRepository->listMappedUserIdWithOneEmployeePosition($companyId, ...$userIds);
	}

	public function getMappingEntitiesForUnmappedPersons(PersonCollection $personCollection): GetMappingEntityCollectionResult
	{
		$collection = new MappingEntityCollection();

		$personIds = $personCollection->getKeys();
		$employees = $this->employeeRepository->getCollectionByPersonIds($personIds);

		/** @var $employeeListByPersonIdMap array<int, EmployeeCollection> */
		$employeeListByPersonIdMap = [];
		foreach ($employees as $employee)
		{
			$employeeListByPersonIdMap[$employee->personId] ??= new EmployeeCollection();
			$employeeListByPersonIdMap[$employee->personId]->add($employee);
		}

		foreach ($personCollection as $person)
		{
			// if no employee for the person, we don't include this person in result
			if (!array_key_exists($person->id, $employeeListByPersonIdMap))
			{
				continue;
			}

			$subTitle = PersonUtil::formatPersonSubtitle($employeeListByPersonIdMap[$person->id]);
			$fullName = PersonUtil::formatFullName($employeeListByPersonIdMap[$person->id]);

			$collection->add(
				new MappingEntity(
					id: $person->id,
					name: $person->title,
					avatarLink: '',
					position: $subTitle,
					fullName: $fullName,
				)
			);
		}

		return new GetMappingEntityCollectionResult($collection);
	}

	/**
	 * @param array $people
	 * @param array $excludeIds
	 * @return GetMatchesForMappingResult
	 */
	public function getSuggestForPeople(array $people, array $excludeIds): GetMatchesForMappingResult
	{
		foreach ($people as &$person)
		{
			$user = Container::getHcmLinkUserRepository()->getUsersIdBySearch($person->name, $excludeIds, 1)->getFirst();
			if ($user !== null)
			{
				$person->suggestId = (int)$user->id;
			}
		}

		return new GetMatchesForMappingResult(array_values($people));
	}

	/**
	 * @param int $companyId
	 * @param array $users
	 * @return GetMatchesForMappingResult
	 */
	public function getSuggestForUsers(int $companyId, array $users): GetMatchesForMappingResult
	{
		foreach ($users as &$user)
		{
			$index = \Bitrix\Main\Search\Content::prepareStringToken($user->name);
			$person = Container::getHcmLinkPersonRepository()->searchByIndexAndCompanyId($index, $companyId, 1)->getFirst();
			if ($person !== null)
			{
				$user->suggestId = (int)$person->id;
			}
		}

		return new GetMatchesForMappingResult(array_values($users));
	}

	public function getEmployeesWithMultipleVacancy(
		int $hcmLinkCompanyId,
		int ...$userIds
	): Main\Result|GetMultipleVacancyEmployeesResult
	{
		$company = Container::getHcmLinkCompanyRepository()->getById($hcmLinkCompanyId);
		if ($company === null)
		{
			return (new Main\Result())->addError(new Main\Error('Company not found'));
		}

		$employees = Container::getHcmLinkEmployeeRepository()->listMultipleVacancyEmployeesByUserIdsAndCompany($hcmLinkCompanyId, ...$userIds);

		usort($employees, static fn($a, $b) => strcmp($a['fullName'], $b['fullName']));
		$employees = array_map(
			static function($key, $value) {
				$value['order'] = $key;
				return $value;
			},
			array_keys($employees),
			array_values($employees),
		);

		return new GetMultipleVacancyEmployeesResult(
			employees: $employees,
		);
	}
}