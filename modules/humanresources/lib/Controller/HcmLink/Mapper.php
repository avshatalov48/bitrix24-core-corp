<?php

namespace Bitrix\HumanResources\Controller\HcmLink;

use Bitrix\HumanResources\Engine\HcmLinkController;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Item\HcmLink\Person;
use Bitrix\HumanResources\Result\Service\HcmLink\FilterNotMappedUserIdsResult;
use Bitrix\HumanResources\Result\Service\HcmLink\GetMappingEntityCollectionResult;
use Bitrix\HumanResources\Result\Service\HcmLink\JobServiceResult;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main;
use Bitrix\Intranet;
use Bitrix\Main\Localization\Loc;

class Mapper extends HcmLinkController
{
	private const MODE_DIRECT = 'direct';
	private const MODE_REVERSE = 'reverse';

	private const LIMIT_USERS_FOR_MAPPER = 50;
	private const LIMIT_PERSONS_FOR_MAPPER = 10;

	public function getDefaultPreFilters(): array
	{
		return [
			new Main\Engine\ActionFilter\ContentType([Main\Engine\ActionFilter\ContentType::JSON]),
			new Main\Engine\ActionFilter\Authentication(),
			new Main\Engine\ActionFilter\HttpMethod(
				[Main\Engine\ActionFilter\HttpMethod::METHOD_POST],
			),
			new Intranet\ActionFilter\IntranetUser(),
		];
	}

	public function loadAction(int $companyId, array $userIds, string $mode): array
	{
		if (!$this->checkAccess())
		{
			return [];
		}

		$items = [];
		$mappedUserIds = [];
		$countMappedPersons = 0;
		$countUnmappedPersons = 0;
		if ($mode === self::MODE_DIRECT)
		{
			$result = Container::getHcmLinkMapperService()->filterNotMappedUserIds($companyId, ...$userIds);
			if ($result instanceof FilterNotMappedUserIdsResult)
			{
				$items = Container::getHcmLinkUserRepository()
					->getMappingEntityCollectionByUserIds($result->userIds, self::LIMIT_USERS_FOR_MAPPER)
					->getValues()
				;
			}

			$countMappedPersons =
				Container::getHcmLinkPersonRepository()->getCountMappedPersonsByUserIds($companyId, $userIds);
		}
		else
		{
			$personRepo = Container::getHcmLinkPersonRepository();
			$personCollection = $personRepo->getUnmappedPersonsByCompanyId($companyId, self::LIMIT_PERSONS_FOR_MAPPER);
			if ($personCollection->count() > 0)
			{
				$result = Container::getHcmLinkMapperService()->getMappingEntitiesForUnmappedPersons($personCollection);
				$items = $result->collection->getValues();

				foreach ($personCollection as $person)
				{
					$personRepo->updateCounter($person->id);
				}

				$mappedUserIds = $personRepo->getMappedUserIdsByCompanyId($companyId);
				$countUnmappedPersons = Container::getHcmLinkPersonRepository()->countUnmappedPersons($companyId);
			}
		}

		$userId = Main\Engine\CurrentUser::get()->getId();
		$isHideInfoAlert = \CUserOptions::GetOption('humanresources', 'hcmlink-mapper-hide-info-alert', false, $userId);

		return compact(
			'items',
			'countMappedPersons',
			'countUnmappedPersons',
			'isHideInfoAlert',
			'mappedUserIds',
		);
	}

	public function saveAction(array $collection, int $companyId): array
	{
		if (!$this->checkAccess())
		{
			return [];
		}

		$company = Container::getHcmLinkCompanyRepository()->getById($companyId);
		if ($company === null)
		{
			$this->addError(new Main\Error(Main\Localization\Loc::getMessage('HUMANRESOURCES_COMPANY_LINK_NOT_FOUND')));

			return [];
		}

		$personRepository = Container::getHcmLinkPersonRepository();
		$map = [];
		foreach ($collection as $item)
		{
			if (empty($item['personId']) || empty($item['userId']))
			{
				continue;
			}

			$map[(int)$item['personId']] = (int)$item['userId'];
		}

		$personCollection = $personRepository->getByIdsExcludeMapped(array_keys($map), $company->id);
		$mappedUserIds = $personRepository->getMappedUserIdsByCompanyId($company->id);
		/** @var string[] $failedMappedPersons */
		$failedPersonsTitles = [];
		if (!empty($map))
		{
			foreach ($personCollection as $person)
			{
				$person->userId = $map[$person->id] ?? 0;
				if (
					in_array($person->userId, $mappedUserIds, true)
					|| $person->userId === 0
				)
				{
					$failedPersonsTitles[] = $person->title;
					continue;
				}

				try
				{
					$personRepository->update($person);
					$mappedUserIds[] = $person->userId;
				}
				catch (UpdateFailedException $e)
				{
					$failedPersonsTitles[] = $person->title;
					continue;
				}
			}
		}

		if (!empty($failedPersonsTitles))
		{
			$failedPersons = implode(', ', $failedPersonsTitles);
			$this->addError(
				new Main\Error(
					Loc::getMessage(
						'HUMANRESOURCES_HCMLINK_FAILED_SAVED',
						[
							'#FAILED_PERSONS_LIST#' => $failedPersons,
						]
					)
				)
			);
		}

		if (($personRepository->countNotMappedAndGroupByCompanyId([$companyId])[$companyId] ?? 0) === 0)
		{
			Container::getHcmLinkCompanyCounterService()->update();
		}

		if (!$personCollection->empty())
		{
			Container::getHcmLinkJobService()->completeMapping($company->id);
		}

		return [];
	}

	public function deleteAction($mappingIds): array
	{
		if (!$this->checkAccess())
		{
			return [];
		}

		$collection = Container::getHcmLinkPersonRepository()->getListByIds($mappingIds);
		if ($collection->count() === 0)
		{
			$this->addError(
				new Main\Error(Main\Localization\Loc::getMessage('HUMANRESOURCES_COMPANY_DATA_IS_INCORRECT'))
			);
		}

		$newCollection = Container::getHcmLinkPersonRepository()->deleteLink($collection);

		if ($newCollection->count() !== $collection->count())
		{
			$this->addError(new Main\Error(Main\Localization\Loc::getMessage('HUMANRESOURCES_HCMLINK_FAILED_DELETE')));
		}

		if (!$newCollection->empty())
		{
			Container::getHcmLinkJobService()->completeMapping($newCollection->getFirst()->companyId);
		}

		return array_map(fn(Person $person) => $person->id, $newCollection->getItemMap());
	}

	public function startAction(int $companyId): array
	{
		if (!$this->checkAccess())
		{
			return [];
		}

		$company = Container::getHcmLinkCompanyRepository()->getById($companyId);
		$result = Container::getHcmLinkJobService()->requestEmployeeList($company->id);
		if ($result instanceof JobServiceResult)
		{
			$jobId = $result->job->id;

			return compact('jobId');
		}

		$this->addErrors($result->getErrors());

		return [];
	}

	public function endAction(int $companyId): array
	{
		if (!$this->checkAccess())
		{
			return [];
		}

		$company = Container::getHcmLinkCompanyRepository()->getById($companyId);
		$result = Container::getHcmLinkJobService()->completeMapping($company->id);
		if ($result instanceof JobServiceResult)
		{
			$jobId = $result->job->id;

			return compact('jobId');
		}

		$this->addErrors($result->getErrors());

		return [];
	}

	public function closeInfoAlertAction(): array
	{
		if (!$this->checkAccess())
		{
			return [];
		}

		$userId = Main\Engine\CurrentUser::get()->getId();
		if ($userId !== null)
		{
			\CUserOptions::SetOption('humanresources', 'hcmlink-mapper-hide-info-alert', true, false, (int)$userId);
		}

		return [];
	}

	public function getJobStatusAction(int $jobId): array
	{
		if (!$this->checkAccess())
		{
			return [];
		}

		$job = Container::getHcmLinkJobRepository()->getById($jobId);
		if ($job === null)
		{
			return [];
		}

		$params = [
			'jobId' => $job->id,
			'status' => $job->status->value,
		];

		return compact('params');
	}

	private function checkAccess(): bool
	{
		if (!Container::getHcmLinkAccessService()->canRead())
		{
			$this->addError($this->makeAccessDeniedError());

			return false;
		}

		return true;
	}
}
