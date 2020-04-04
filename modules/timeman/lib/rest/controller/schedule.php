<?php
namespace Bitrix\Timeman\Rest\Controller;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Main\ORM\Fields\FieldTypeMask;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Timeman\Form\Schedule\ScheduleForm;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Helper\Form\Schedule\ScheduleFormHelper;
use Bitrix\Timeman\Model\Schedule\Schedule as ScheduleEntity;
use Bitrix\Timeman\Rest\Exception\EntityNotFoundException;
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

	protected function init()
	{
		parent::init();
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
					throw new EntityNotFoundException(Loc::getMessage('TIMEMAN_ERROR_SCHEDULE_NOT_FOUND'));
				}
				return $schedule;
			}),
		];
	}

	public function getSchedulesForEntityAction($entityCode)
	{
		$schedulesForEntity = [];
		if ($this->getRequest()->getPost('checkNestedEntities'))
		{
			$codes = [$entityCode];
			if ($entityCode === EntityCodesHelper::getAllUsersCode())
			{
				$id = $this->departmentRepository
					->getBaseDepartmentId();
				if ($id > 0)
				{
					$codes[] = EntityCodesHelper::buildDepartmentCode($id);
				}
			}
			$schedule = null;
			if ($this->getRequest()->getPost('currentScheduleId') > 0)
			{
				$schedule = $this->scheduleRepository
					->findByIdWith($this->getRequest()->getPost('currentScheduleId'), [
						'DEPARTMENT_ASSIGNMENTS',
						'USER_ASSIGNMENTS',
					]);
			}
			return (new ScheduleFormHelper())
				->calculateScheduleAssignmentsMap($codes, $schedule);
		}

		$schedulesMap = $this->scheduleRepository
			->findSchedulesByEntityCodes([$entityCode], ['select' => ['ID', 'NAME', 'IS_FOR_ALL_USERS']]);

		foreach ($schedulesMap as $schedules)
		{
			foreach ($schedules as $schedule)
			{
				$schedulesForEntity[] = array_merge(
					$schedule->collectValues(),
					[
						'LINKS' => [
							'DETAIL' => DependencyManager::getInstance()->getUrlManager()
								->getUriTo(TimemanUrlManager::URI_SCHEDULE_UPDATE, ['SCHEDULE_ID' => $schedule->getId()]),
						],
					]
				);
			}
		}
		return [
			'entityCode' => $entityCode,
			'schedules' => $schedulesForEntity,
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
		$schedule = $this->scheduleRepository->findByIdWith($id, ['SCHEDULE_VIOLATION_RULES', 'SHIFTS', 'CALENDAR', 'CALENDAR.EXCLUSIONS', 'DEPARTMENTS', 'USER_ASSIGNMENTS']);

		if (!$schedule)
		{
			$this->addError(new Main\Error('Schedule not found', 'TIMEMAN_SCHEDULE_GET_SCHEDULE_NOT_FOUND'));
			return [];
		}
		$result = $schedule->collectValues(Values::ALL, $fieldsMask = FieldTypeMask::SCALAR);
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
		return [
			'schedule' => [
				'id' => (int)$schedule->getId(),
				'name' => $schedule->getName(),
				'scheduleType' => $schedule->getScheduleType(),
				'reportPeriod' => $schedule->getReportPeriod(),
				'formattedType' => $schedule->getFormattedType(),
				'formattedPeriod' => $schedule->getFormattedPeriod(),
				'userCount' => $schedule->obtainUsersCount() >= 0 ? $schedule->obtainUsersCount() : '',
			],
		];
	}
}