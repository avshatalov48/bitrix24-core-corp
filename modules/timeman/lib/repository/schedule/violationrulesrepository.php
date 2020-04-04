<?php
namespace Bitrix\Timeman\Repository\Schedule;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Result;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesCollection;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesTable;
use Bitrix\Timeman\Repository\DepartmentRepository;

class ViolationRulesRepository
{
	/** @var ScheduleRepository */
	private $scheduleRepository;
	/** @var DepartmentRepository */
	private $departmentRepository;

	public function __construct(ScheduleRepository $scheduleRepository, DepartmentRepository $departmentRepository)
	{
		$this->scheduleRepository = $scheduleRepository;
		$this->departmentRepository = $departmentRepository;
	}

	public function save(ViolationRules $rules)
	{
		return $rules->save();
	}

	public function findFirstByScheduleIdAndEntityCode($scheduleId, $entityCode)
	{
		$entitiesCodesData = [];
		if (preg_match('#U[0-9]+#', $entityCode) === 1)
		{
			$userId = (int)substr($entityCode, 1);
			$entitiesCodesData = $this->departmentRepository->buildUserDepartmentsPriorityTree($userId);
		}
		elseif (preg_match('#DR[0-9]+#', $entityCode) === 1)
		{
			$departmentId = (int)substr($entityCode, 2);
			$entitiesCodesData[] = $this->departmentRepository->buildDepartmentsPriorityTree($departmentId);
		}
		$uniqueCodes = [];
		foreach ($entitiesCodesData as $entityCodeValues)
		{
			$uniqueCodes = array_merge($uniqueCodes, $entityCodeValues);
		}
		$uniqueCodes = array_unique($uniqueCodes);
		if (empty($uniqueCodes))
		{
			return [];
		}

		$violationRulesList = ViolationRulesTable::query()
			->addSelect('*')
			->whereIn('ENTITY_CODE', $uniqueCodes)
			->where('SCHEDULE_ID', $scheduleId)
			->exec()
			->fetchCollection();
		foreach ($entitiesCodesData as $entityCodes)
		{
			foreach ($entityCodes as $entityCode)
			{
				foreach ($violationRulesList as $violationRules)
				{
					if ($violationRules->getEntityCode() === $entityCode)
					{
						return $violationRules;
					}
				}
			}
		}

		return null;
	}

	/**
	 * @param $scheduleId
	 * @param $entityCode
	 * @return ViolationRules|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findByScheduleIdEntityCode($scheduleId, $entityCode)
	{
		return ViolationRulesTable::query()
			->addSelect('*')
			->where('SCHEDULE_ID', $scheduleId)
			->where('ENTITY_CODE', $entityCode)
			->exec()
			->fetchObject();
	}

	public function findScheduleById($scheduleId)
	{
		return $this->scheduleRepository->findById($scheduleId);
	}

	/**
	 * @param int $scheduleId
	 * @param array $fieldsToSelect
	 * @param ConditionTree $filter
	 * @return ViolationRulesCollection
	 */
	public function findAllByScheduleId($scheduleId, $fieldsToSelect, $filter = null)
	{
		$resultQuery = ViolationRulesTable::query()
			->where('SCHEDULE_ID', $scheduleId);
		foreach ($fieldsToSelect as $fieldToSelect)
		{
			$resultQuery->addSelect($fieldToSelect);
		}
		if ($filter)
		{
			$resultQuery->where($filter);
		}
		return $resultQuery->exec()
			->fetchCollection();
	}

	/**
	 * @param ViolationRulesCollection $violationRulesList
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function saveAll($violationRulesList, $fieldsData)
	{
		if (!empty($violationRulesList->getIdList()))
		{
			return ViolationRulesTable::updateMulti($violationRulesList->getIdList(), $fieldsData, true);
		}
		return new Result();
	}
}