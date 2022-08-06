<?php
namespace Bitrix\Timeman\Controller;

use Bitrix\Main;
use Bitrix\Main\ORM\Fields\FieldTypeMask;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Timeman\Form\Schedule\ScheduleForm;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Helper\Form\Schedule\ScheduleFormHelper;
use Bitrix\Timeman\Model\Schedule\Schedule as ScheduleEntity;
use Bitrix\Timeman\Controller\Exception\EntityNotFoundException;
use Bitrix\Timeman\Security\UserPermissionsManager;
use Bitrix\Timeman\Service\BaseServiceResult;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\TimemanUrlManager;
use Bitrix\Timeman\UseCase\Schedule as ScheduleHandler;
use Bitrix\Main\Engine\Controller;

class Schedule extends Controller
{
	/** @var \Bitrix\Timeman\Repository\DepartmentRepository */
	private $departmentRepository;
	/** @var \Bitrix\Timeman\Repository\Schedule\ScheduleRepository */
	private $scheduleRepository;
	/** @var UserPermissionsManager */
	private $userPermissionsManager;

	protected function init()
	{
		parent::init();
		global $USER;
		$this->userPermissionsManager = DependencyManager::getInstance()->getUserPermissionsManager($USER);
		$this->departmentRepository = DependencyManager::getInstance()->getDepartmentRepository();
		$this->scheduleRepository = DependencyManager::getInstance()->getScheduleRepository();
	}

	public function getAutoWiredParameters()
	{
		return [
			new Main\Engine\AutoWire\ExactParameter(ScheduleEntity::class, 'schedule', function ($className, $id) {
				$schedule = DependencyManager::getInstance()->getScheduleRepository()->findById($id);
				if (!$schedule)
				{
					throw new EntityNotFoundException('Schedule not found');
				}
				return $schedule;
			}),
		];
	}

	public function getSchedulesForScheduleFormAction($exceptScheduleId = null, $exceptScheduleAssignmentCodes = [], $checkNestedEntities = false)
	{
		$scheduleFormHelper = new ScheduleFormHelper();
		if ($exceptScheduleId <= 0 && empty($exceptScheduleAssignmentCodes))
		{
			$this->addError(new Main\Error('Schedule id or assignment codes are required'));
			return [];
		}

		$schedule = null;
		if ($exceptScheduleId > 0)
		{
			$schedule = $this->scheduleRepository
				->findByIdWith($exceptScheduleId, [
					'DEPARTMENT_ASSIGNMENTS',
					'USER_ASSIGNMENTS',
				]);
		}
		if (!$schedule)
		{
			$schedule = (new ScheduleEntity(false))->setId(0);
		}
		$schedule->removeAllUserAssignments();
		$schedule->removeAllDepartmentAssignments();
		foreach ($exceptScheduleAssignmentCodes as $codeData)
		{
			$schedule->assignEntity($codeData['code'], $codeData['excluded'] === 'true');
			if (EntityCodesHelper::isAllUsers($codeData['code']))
			{
				$schedule->setIsForAllUsers(true);
			}
		}

		$schedulesForEntityCodes = $scheduleFormHelper->calculateSchedulesMapBySchedule(
			$schedule,
			$checkNestedEntities
		);

		return [
			'schedules' => $schedulesForEntityCodes,
		];
	}

	public function getSchedulesForEntityAction($entityCode)
	{
		$schedulesForEntityCodes = $this->scheduleRepository->findSchedulesByEntityCodes([$entityCode]);
		$result = [];
		foreach ((array)$schedulesForEntityCodes[$entityCode] as $schedule)
		{
			$result[] =
				array_merge(
					$schedule->collectRawValues(),
					[
						'LINKS' => [
							'DETAIL' => DependencyManager::getInstance()->getUrlManager()
								->getUriTo(TimemanUrlManager::URI_SCHEDULE_UPDATE, ['SCHEDULE_ID' => $schedule->getId()]),
						],
					]
				);
		}
		return [
			'entityCode' => $entityCode,
			'schedules' => $result,
		];
	}

	public function addAction()
	{
		$scheduleForm = new ScheduleForm();

		if ($scheduleForm->load($this->getRequest()) && $scheduleForm->validate())
		{
			$result = (new ScheduleHandler\Create\Handler())->handle($scheduleForm);
			if (BaseServiceResult::isSuccessResult($result))
			{
				return $this->makeResult($result);
			}
			$this->addErrors($result->getErrors());
			return [];
		}
		$this->addError($scheduleForm->getFirstError());
	}

	public function updateAction()
	{
		$scheduleForm = new ScheduleForm();

		if ($scheduleForm->load($this->getRequest()) && $scheduleForm->validate())
		{
			$result = (new ScheduleHandler\Update\Handler())->handle($scheduleForm);

			if (BaseServiceResult::isSuccessResult($result))
			{
				return $this->makeResult($result);
			}
			$this->addErrors($result->getErrors());
			return [];
		}
		$this->addError($scheduleForm->getFirstError());
	}

	public function getAction($id)
	{
		$schedule = $this->scheduleRepository->findByIdWith($id, [
			'SCHEDULE_VIOLATION_RULES', 'SHIFTS', 'CALENDAR', 'CALENDAR.EXCLUSIONS', 'DEPARTMENTS', 'USER_ASSIGNMENTS'
		]);

		if (!$schedule)
		{
			$this->addError(new Main\Error('Schedule not found', 'TIMEMAN_SCHEDULE_GET_SCHEDULE_NOT_FOUND'));
			return [];
		}

		if (!$this->userPermissionsManager->canReadSchedule($id))
		{
			$this->addError(new Main\Error('Access denied', 'TIMEMAN_SCHEDULE_GET_SCHEDULE_ACCESS_DENIED'));

			return [];
		}

		$result = $schedule->collectRawValues();
		foreach ($schedule->obtainShifts() as $shift)
		{
			$result['SHIFTS'][] = $shift->collectValues(Values::ALL, $fieldsMask = FieldTypeMask::SCALAR);
		}
		if ($schedule->get('CALENDAR'))
		{
			/** @var \Bitrix\Timeman\Model\Schedule\Calendar\Calendar $calendar */
			$calendar = $schedule->get('CALENDAR');
			$result['CALENDAR'] = $calendar->collectValues(Values::ALL, $fieldsMask = FieldTypeMask::SCALAR);
			$result['CALENDAR']['EXCLUSIONS'] = $calendar->obtainFinalExclusions();
		}
		foreach ($schedule->obtainDepartmentAssignments() as $departmentAssignment)
		{
			$result['USER_ASSIGNMENTS'][] = $departmentAssignment->collectValues(Values::ALL, $fieldsMask = FieldTypeMask::SCALAR);
		}
		foreach ($schedule->obtainUserAssignments() as $userAssignment)
		{
			$result['DEPARTMENT_ASSIGNMENTS'][] = $userAssignment->collectValues(Values::ALL, $fieldsMask = FieldTypeMask::SCALAR);
		}
		$rules = $schedule->obtainScheduleViolationRules();
		if ($rules->getId() > 0)
		{
			$result['SCHEDULE_VIOLATION_RULES'] = $rules->collectValues(Values::ALL, $fieldsMask = FieldTypeMask::SCALAR);
		}

		return $result;
	}

	public function deleteListAction($ids)
	{
		foreach ($ids as $id)
		{
			if ($this->errorCollection->isEmpty())
			{
				$this->deleteAction($id);
			}
		}
	}

	public function deleteAction($id)
	{
		$result = (new ScheduleHandler\Delete\Handler())->handle($id);

		if (!BaseServiceResult::isSuccessResult($result))
		{
			$this->addErrors($result->getErrors());
		}
	}


	public function deleteUserAction(ScheduleEntity $schedule, $userId)
	{
		$result = (new ScheduleHandler\Assignment\Delete\Handler())->deleteUsers($schedule->getId(), [$userId]);

		if ($result->isSuccess())
		{
			return $this->makeResult($result);
		}
		$this->addErrors($result->getErrors());
	}

	public function addUserAction(ScheduleEntity $schedule, $userId)
	{
		$result = (new ScheduleHandler\Assignment\Create\Handler())->addUsers($schedule->getId(), [$userId]);

		if ($result->isSuccess())
		{
			return $this->makeResult($result);
		}
		$this->addErrors($result->getErrors());
	}

	/**
	 * @param BaseServiceResult $result
	 * @return array
	 */
	private function makeResult($result)
	{
		/** @var ScheduleEntity $schedule */
		$schedule = $result->getSchedule();
		$links = [
			'update' => DependencyManager::getInstance()->getUrlManager()
				->getUriTo(TimemanUrlManager::URI_SCHEDULE_UPDATE, ['SCHEDULE_ID' => $schedule->getId()]),
		];
		if ($schedule->isShifted())
		{
			$links['shiftPlan'] = DependencyManager::getInstance()->getUrlManager()
				->getUriTo(TimemanUrlManager::URI_SCHEDULE_SHIFTPLAN, ['SCHEDULE_ID' => $schedule->getId()]);
		}
		$scheduleFormHelper = new ScheduleFormHelper();
		return [
			'schedule' => [
				'id' => (int)$schedule->getId(),
				'name' => $schedule->getName(),
				'scheduleType' => $schedule->getScheduleType(),
				'reportPeriod' => $schedule->getReportPeriod(),
				'formattedType' => $scheduleFormHelper->getFormattedType($schedule->getScheduleType()),
				'formattedPeriod' => $scheduleFormHelper->getFormattedPeriod($schedule->getReportPeriod()),
				'userCount' => $schedule->obtainUsersCount() >= 0 ? $schedule->obtainUsersCount() : '',
				'canReadShiftPlan' => $this->userPermissionsManager->canReadShiftPlan($schedule->getId()),
				'links' => $links,
			],
		];
	}
}