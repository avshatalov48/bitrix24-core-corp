<?php
namespace Bitrix\Timeman\Service\Worktime\Record;

use Bitrix\Timeman\Form\Worktime\WorktimeRecordForm;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable;
use Bitrix\Timeman\Repository\Schedule\ViolationRulesRepository;
use Bitrix\Timeman\Repository\Worktime\WorktimeRepository;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolationManager;

class WorktimeManagerFactory
{
	private $violationManager;
	private $worktimeRepository;
	private $violationRulesRepository;


	public function __construct(WorktimeViolationManager $violationManager,
								ViolationRulesRepository $violationRulesRepository,
								WorktimeRepository $worktimeRepository
	)
	{
		$this->violationManager = $violationManager;
		$this->worktimeRepository = $worktimeRepository;
		$this->violationRulesRepository = $violationRulesRepository;
	}

	/**
	 * @param WorktimeRecordForm $recordForm
	 * @return WorktimeManager|null
	 * @throws \Bitrix\Main\SystemException
	 */
	public function buildManager($recordForm): ?WorktimeManager
	{
		$params = [$this->violationManager, $this->violationRulesRepository, $recordForm, $this->worktimeRepository];
		switch ($recordForm->getFirstEventForm()->eventName)
		{
			case WorktimeEventTable::EVENT_TYPE_EDIT_WORKTIME:
			case WorktimeEventTable::EVENT_TYPE_EDIT_STOP:
			case WorktimeEventTable::EVENT_TYPE_EDIT_BREAK_LENGTH:
			case WorktimeEventTable::EVENT_TYPE_EDIT_START:
				return new EditWorktimeManager(...$params);
			case WorktimeEventTable::EVENT_TYPE_START:
				return new StartWorktimeManager(...$params);
			case WorktimeEventTable::EVENT_TYPE_START_WITH_ANOTHER_TIME:
				return new StartCustomTimeWorktimeManager(...$params);
			case WorktimeEventTable::EVENT_TYPE_STOP:
				return new StopWorktimeManager(...$params);
			case WorktimeEventTable::EVENT_TYPE_STOP_WITH_ANOTHER_TIME:
				return new StopCustomTimeWorktimeManager(...$params);
			case WorktimeEventTable::EVENT_TYPE_APPROVE:
				return new ApproveWorktimeManager(...$params);
			case WorktimeEventTable::EVENT_TYPE_CONTINUE:
			case WorktimeEventTable::EVENT_TYPE_RELAUNCH:
				return new ContinueWorktimeManager(...$params);
			case WorktimeEventTable::EVENT_TYPE_PAUSE:
				return new PauseWorktimeManager(...$params);
		}
		return null;
	}
}