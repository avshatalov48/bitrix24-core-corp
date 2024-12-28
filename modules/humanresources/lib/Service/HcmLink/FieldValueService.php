<?php

namespace Bitrix\HumanResources\Service\HcmLink;

use Bitrix\HumanResources\Contract\Repository\HcmLink\CompanyRepository;
use Bitrix\HumanResources\Contract\Repository\HcmLink\EmployeeRepository;
use Bitrix\HumanResources\Contract\Repository\HcmLink\FieldRepository;
use Bitrix\HumanResources\Contract\Repository\HcmLink\FieldValueRepository;
use Bitrix\HumanResources\Item\HcmLink\Employee;
use Bitrix\HumanResources\Item\HcmLink\FieldValue;
use Bitrix\HumanResources\Result\Service\HcmLink\GetFieldValueResult;
use Bitrix\HumanResources\Result\Service\HcmLink\JobServiceResult;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class FieldValueService implements \Bitrix\HumanResources\Contract\Service\HcmLink\FieldValueService
{
	private CompanyRepository $companyRepository;
	private EmployeeRepository $employeeRepository;
	private FieldRepository $fieldRepository;
	private FieldValueRepository $fieldValueRepository;

	public function __construct(
		CompanyRepository $companyRepository = null,
		EmployeeRepository $employeeRepository = null,
		FieldRepository $fieldRepository = null,
		FieldValueRepository $fieldValueRepository = null
	)
	{
		$this->companyRepository = $companyRepository ?? Container::getHcmLinkCompanyRepository();
		$this->employeeRepository = $employeeRepository ?? Container::getHcmLinkEmployeeRepository();
		$this->fieldRepository = $fieldRepository ?? Container::getHcmLinkFieldRepository();
		$this->fieldValueRepository = $fieldValueRepository ?? Container::getHcmLinkFieldValueRepository();
	}

	/**
	 * @param int $companyId
	 * @param int[] $employeeIds
	 * @param int[] $fieldIds
	 *
	 * @return Result|JobServiceResult
	 */
	public function requestFieldValue(int $companyId, array $employeeIds, array $fieldIds): Result|JobServiceResult
	{
		$company = $this->companyRepository->getById($companyId);
		if ($company === null || $company->id === null)
		{
			return (new Result())->addError(new Error('Company not found'));
		}

		$employeeUuids = array_values(array_map(
			fn(Employee $employee) => $employee->code,
			$this->employeeRepository->getByIds($employeeIds)->getItemMap()
		));

		if (empty($employeeUuids))
		{
			return (new Result())->addError(new Error('You should pass correct employee ids'));
		}

		$fieldUids = [];
		$savedFields = $this->fieldRepository->getByCompany($company->id)->getItemMap();
		foreach ($fieldIds as $fieldId)
		{
			$fieldUid = $savedFields[$fieldId]?->field ?? null;
			if ($fieldUid !== null)
			{
				$fieldUids[] = $savedFields[$fieldId]?->field;
			}
		}

		if (empty($fieldUids))
		{
			return (new Result())->addError(new Error('You should pass correct field ids'));
		}

		return Container::getHcmLinkJobService()->requestFieldValue(
			$company->id,
			$employeeUuids,
			$fieldUids
		);
	}

	/**
	 * @param array $employeeIds
	 * @param array $fieldIds
	 *
	 * @return Result|GetFieldValueResult
	 */
	public function getFieldValue(array $employeeIds, array $fieldIds): Result|GetFieldValueResult
	{
		$collection = $this->fieldValueRepository->getByFieldIdsAndEmployeeIds($fieldIds, $employeeIds);
		$isActual = true;

		/** @var FieldValue $item */
		foreach ($collection as $item)
		{
			if ($item->expiredAt->getTimestamp() > (time() - 100400))
			{
				$isActual = false;

				break;
			}

		}

		return new GetFieldValueResult($collection, $isActual);
	}
}