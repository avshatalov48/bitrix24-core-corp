<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\Tasks;

use Bitrix\Crm\Activity\Provider\Tasks\TaskActivityStatus;
use Bitrix\Crm\Integration\Tasks\TaskAccessController;
use Bitrix\Crm\Integration\Tasks\TaskChecklist;
use Bitrix\Crm\Integration\Tasks\TaskObject;
use Bitrix\Crm\Integration\Tasks\TaskPathMaker;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Item\AssociatedEntityModel;
use Bitrix\Crm\Service\Timeline\Layout\Action\Analytics;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Date;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\EditableDate;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\FileList;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Link;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Common\Logo;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItem;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;

class Task extends Activity
{
	use ActivityTrait;

	private const ACTIVITY_TYPE_ID = 'TasksTask';

	protected function getActivityTypeId(): string
	{
		return self::ACTIVITY_TYPE_ID;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('TASKS_TASK_DEAL_TITLE');
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	public function getIconCode(): ?string
	{
		return Icon::TASK_ACTIVITY;
	}

	public function getTags(): ?array
	{
		$model = $this->getAssociatedEntityModel();

		return [
			'Status' => $this->getStatusBlock($model),
		];
	}

	public function getDate(): ?DateTime
	{
		$updatedDate = $this->getAssociatedEntityModel()->get('LAST_UPDATED');

		return is_null($updatedDate) ? parent::getDate() : DateTime::createFromUserTime($updatedDate);
	}

	public function getContentBlocks(): ?array
	{
		$model = $this->getAssociatedEntityModel();
		$task = $this->getTask($model);
		if (is_null($task))
		{
			return null;
		}

		$blocks['Deadline'] = $this->getDeadlineBlock($model, $task);
		$blocks['Title'] = $this->getTaskTitleBlock($model, $task);
		$blocks['MobileTitle'] = $this->getTaskTitleBlockMobile($model, $task);
		$blocks['Responsible'] = $this->getResponsibleBlock($model, $task);

		$files = $this->getFiles();

		if (!empty($files))
		{
			$blocks['Files'] = $this->getFilesBlock($files);
		}

		$checklistItemsCount = $this->getChecklistItemsCount($model);
		if ($checklistItemsCount['TOTAL'] !== 0)
		{
			$blocks['Checklist'] = $this->getChecklistBlock($checklistItemsCount);
		}

		return $blocks;
	}

	public function getButtons(): ?array
	{
		$model = $this->getAssociatedEntityModel();
		$task = $this->getTask($model);

		if (is_null($task))
		{
			return null;
		}

		if ($task->isCompleted())
		{
			return [
				'Open' => $this->getOpenButton($model, $task, Button::TYPE_SECONDARY),
			];
		}

		return [
			'Open' => $this->getOpenButton($model, $task),
			'Ping' => $this->getPingButton($model, $task),
		];
	}

	public function getMenuItems(): array
	{
		$model = $this->getAssociatedEntityModel();
		$task = $this->getTask($model);

		if (is_null($task))
		{
			return [];
		}

		$menuItems['edit'] =  $this->createEditTaskMenuItem($task);
		$menuItems['delete'] =  $this->createDeleteTaskMenuItem($task);

		return $menuItems;
	}

	public function getLogo(): \Bitrix\Crm\Service\Timeline\Layout\Body\Logo
	{
		$logo = Logo::getInstance(Logo::TASK_ACTIVITY)
			->createLogo()
		;

		return $logo;
	}
	public function getDeadlineBlock(AssociatedEntityModel $model, \Bitrix\Tasks\Internals\TaskObject $task): ContentBlockWithTitle
	{
		$action = new JsEvent('Task:ChangeDeadline');
		$action->addActionParamInt('taskId', $task->getId());
		$deadlineBlockObject = new ContentBlockWithTitle();
		$deadline = $task->getDeadline();
		if (!is_null($deadline))
		{
			$contentBlockObject = new EditableDate();
			$contentBlockObject
				->setReadonly(!$this->isScheduled() || !$this->hasUpdatePermission())
				->setStyle(EditableDate::STYLE_PILL)
				->setDate($deadline)
				->setAction($action)
				->setBackgroundColor(EditableDate::BACKGROUND_COLOR_WARNING)
			;
		}
		else
		{
			$contentBlockObject = new Text();
			$contentBlockObject->setValue(Loc::getMessage('TASKS_TASK_DEAL_EMPTY_DEADLINE'));
		}
		$deadlineBlockObject
			->setAlignItems('center')
			->setInline()
			->setTitle(Loc::getMessage('TASKS_TASK_DEAL_DEADLINE'))
			->setContentBlock($contentBlockObject)
		;

		return $deadlineBlockObject;
	}

	public function getResponsibleBlock(AssociatedEntityModel $model, \Bitrix\Tasks\Internals\TaskObject $task): ContentBlockWithTitle
	{
		// TODO
		$deadlineBlockObject = new ContentBlockWithTitle();
		$deadlineBlockObject
			->setInline()
			->setTitle(Loc::getMessage('TASKS_TASK_DEAL_ASSIGNEE'))
			->setContentBlock(
				(new Link())
					->setValue($this->getUserName($task->getResponsibleMemberId()))
					->setAction(new Redirect(new Uri($this->getUserUrl($task->getResponsibleMemberId()))))
					->setIsBold(true)
			);

		return $deadlineBlockObject;
	}

	public function getFilesBlock(array $files): FileList
	{
		$filesBlockObject = new FileList();
		$filesBlockObject
			->setFiles($files)
			->setTitle(Loc::getMessage('TASKS_TASK_DEAL_FILES'))
		;

		return $filesBlockObject;
	}

	public function getChecklistBlock(array $checklistItemsCount): ContentBlockWithTitle
	{
		$deadlineBlockObject = new ContentBlockWithTitle();
		$deadlineBlockObject
			->setInline()
			->setTitle(Loc::getMessage('TASKS_TASK_DEAL_CHECKLIST'))
			->setContentBlock(
				(new Text())
					->setValue($checklistItemsCount['COMPLETED'] . '/' . $checklistItemsCount['TOTAL'])
					->setIsBold(true)
					->setColor(Text::COLOR_BASE_50)
			);

		return $deadlineBlockObject;
	}

	public function getOpenButton(
		AssociatedEntityModel $model,
		\Bitrix\Tasks\Internals\TaskObject $task,
		string $type = Button::TYPE_PRIMARY
	): Button
	{
		$openButtonObject = new Button(
			Loc::getMessage('TASKS_TASK_DEAL_BUTTON_OPEN'),
			$type
		);

		$openButtonObject->setAction($this->getTaskAction($task, 'view_button'));

		return $openButtonObject;
	}

	public function getPingButton(AssociatedEntityModel $model, \Bitrix\Tasks\Internals\TaskObject $task): Button
	{
		$pingButtonObject = new Button(
			Loc::getMessage('TASKS_TASK_DEAL_BUTTON_PING'),
			Button::TYPE_SECONDARY
		);

		$action = new JsEvent('Task:Ping');
		$action->addActionParamInt('taskId', $task->getId());
//		$action->setAnalytics(new Analytics(['scenario' => 'task_ping'], $this->analyticsHit));
		$pingButtonObject->setAction($action);

		return $pingButtonObject;
	}

	private function getUserName(?int $userId): string
	{
		if (is_null($userId))
		{
			return '';
		}

		return Container::getInstance()->getUserBroker()->getName($userId);
	}

	private function getStatusBlock(AssociatedEntityModel $model): Tag
	{
		$data = $model->get('SETTINGS');
		$status = $data['ACTIVITY_STATUS'] ?? 'Undefined';
		$activityStatus = new TaskActivityStatus();
		return new Tag(
			$activityStatus->getStatusLocMessage($status),
			$activityStatus->getIcon($status)
		);
	}

	private function getChecklistItemsCount(AssociatedEntityModel $model): array
	{
		$data = $model->get('SETTINGS');
		$taskId = $data['TASK_ID'];

		return [
			'TOTAL' => count(TaskChecklist::getNotRootChecklistItems($taskId)),
			'COMPLETED' => count(TaskChecklist::getCompletedChecklistItemsCount($taskId)),
		];
	}

	private function getColorByStatus(int $status)
	{

	}

	private function createEditTaskMenuItem(\Bitrix\Tasks\Internals\TaskObject $task): MenuItem
	{
		$pathMaker = TaskPathMaker::getPathMaker($task->getId(), $this->getContext()->getUserId(), 'edit');

		$event = new JsEvent('Task:Edit');
		$event
			->addActionParamString('path', $pathMaker->makeEntityPath())
			->addActionParamInt('taskId', $task->getId())
			->addActionParamString('taskTitle', $task->getTitle())
		;
		$item = new MenuItem(Loc::getMessage('TASKS_TASK_DEAL_SUBMENU_EDIT'));
		$item->setAction($event);

		return $item;
	}

	private function createDeleteTaskMenuItem(\Bitrix\Tasks\Internals\TaskObject $task): MenuItem
	{
		$event = new JsEvent('Task:Delete');
		$event
			->addActionParamInt('taskId', $task->getId())
			->addActionParamString('taskTitle', $task->getTitle())
		;
		$item = new MenuItem(Loc::getMessage('TASKS_TASK_DEAL_SUBMENU_DELETE'));
		$item->setAction($event);

		return $item;
	}
}