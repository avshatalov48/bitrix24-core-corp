<?php
namespace Bitrix\Timeman\Repository\Schedule;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Result;
use Bitrix\Timeman\Helper\EntityCodesHelper;
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

	public function findFirstByScheduleIdAndEntityCode($scheduleId, $entityCode): ?ViolationRules
	{
		$possibleEntityCodesForRules = [];
		if (EntityCodesHelper::isUser($entityCode))
		{
			$userId = EntityCodesHelper::getUserId($entityCode);
			$possibleEntityCodesForRules = $this->departmentRepository->buildUserDepartmentsPriorityTrees($userId);
		}
		elseif (EntityCodesHelper::isDepartment($entityCode))
		{
			$departmentId = EntityCodesHelper::getDepartmentId($entityCode);
			$possibleEntityCodesForRules[] = $this->departmentRepository->buildDepartmentsPriorityTree($departmentId);
		}
		$uniqueCodes = [];
		foreach ($possibleEntityCodesForRules as $entityCodeValues)
		{
			$uniqueCodes[] = $entityCodeValues;
		}
		if (empty($uniqueCodes))
		{
			return null;
		}

		$uniqueCodes = array_merge(...$uniqueCodes);
		$uniqueCodes = array_unique($uniqueCodes);
		$violationRulesList = ViolationRulesTable::query()
			->addSelect('*')
			->whereIn('ENTITY_CODE', $uniqueCodes)
			->where('SCHEDULE_ID', $scheduleId)
			->exec()
			->fetchCollection();
		foreach ($possibleEntityCodesForRules as $entitiesCodesTreeByPriority)
		{
			foreach ($entitiesCodesTreeByPriority as $entityCodeFromTree)
			{
				foreach ($violationRulesList as $violationRules)
				{
					if ($violationRules->getEntityCode() === $entityCodeFromTree)
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