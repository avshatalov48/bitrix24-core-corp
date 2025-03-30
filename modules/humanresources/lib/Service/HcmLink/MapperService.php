<?php

namespace Bitrix\HumanResources\Service\HcmLink;

use Bitrix\HumanResources\Item\Collection\HcmLink\MappingEntityCollection;
use Bitrix\HumanResources\Item\Collection\HcmLink\PersonCollection;
use Bitrix\HumanResources\Item\HcmLink\MappingEntity;
use Bitrix\HumanResources\Result\Service\HcmLink\FilterNotMappedUserIdsResult;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Result\Service\HcmLink\GetMappingEntityCollectionResult;
use Bitrix\HumanResources\Result\Service\HcmLink\GetMatchesForMappingResult;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Result\Service\HcmLink\GetMultipleVacancyEmployeesResult;
use Bitrix\HumanResources\Type\HcmLink\FieldType;
use Bitrix\Main;
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
				new Main\Error('Integration not found', 'HR_HCMLINK_INTEGRATION_NOT_FOUND'),
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
		foreach ($employees as $employee)
		{
			$person = $personCollection->getItemById($employee->personId);
			if ($person === null)
			{
				continue;
			}

			$position = '';
			if (!empty($employee->data[FieldType::POSITION->name]))
			{
				$position = (string)$employee->data[FieldType::POSITION->name];
			}

			if (isset($items[$person->id]))
			{
				/** @var MappingEntity $entity */
				$entity = $items[$person->id];
				$additionalPosition = !empty($entity->position) ? ", {$position}" : $position;
				$position = $entity->position . $additionalPosition;
			}

			$collection->add(
				new MappingEntity(
					id: $person->id,
					name: $person->title,
					avatarLink: '',
					position: $position,
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