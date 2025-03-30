<?php

namespace Bitrix\Crm\Service\Timeline\Item\Bizproc;

use Bitrix\Bizproc\Workflow\Entity\WorkflowDurationStatTable;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Main\Web\Uri;
use Bitrix\Bizproc\Workflow\Entity\WorkflowStateTable;
use Bitrix\Main\Loader;

trait Workflow
{
	static $usersLimit = 2;

	protected function createDeleteMenuItem($model): ?Layout\Menu\MenuItem
	{
		if ($model->getAssociatedEntityTypeId() !== \CCrmOwnerType::Activity)
		{
			return null;
		}

		$activityId = $model->getAssociatedEntityId();
		$ownerTypeId = $model->getAssociatedEntityModel()?->get('OWNER_TYPE_ID');
		$ownerId = $model->getAssociatedEntityModel()?->get('OWNER_ID');

		return (new Layout\Menu\MenuItem(Loc::getMessage('CRM_TIMELINE_BIZPROC_BTN_DELETE_TEXT') ?? ''))
			->setAction(
				(new Layout\Action\RunAjaxAction('crm.timeline.activity.delete'))
					->addActionParamInt('activityId', $activityId)
					->addActionParamInt('ownerTypeId', $ownerTypeId)
					->addActionParamInt('ownerId', $ownerId)
					->setAnimation(Layout\Action\Animation::disableItem())
			)
		;
	}

	protected function createTerminateMenuItem(string $workflowId): ?Layout\Menu\MenuItem
	{
		if (!$this->isBizprocEnabled())
		{
			return null;
		}

		return (new Layout\Menu\MenuItem(Loc::getMessage('CRM_TIMELINE_BIZPROC_BTN_TERMINATE_TEXT') ?? ''))
			->setAction(
				(new JsEvent('Bizproc:Workflow:Terminate'))
					->addActionParamString('workflowId', $workflowId)
					->setAnimation(Layout\Action\Animation::disableItem())
			)
		;
	}

	protected function createTimelineMenuItem(string $workflowId): ?Layout\Menu\MenuItem
	{
		if (!$this->isBizprocEnabled())
		{
			return null;
		}

		return (new Layout\Menu\MenuItem(Loc::getMessage('CRM_TIMELINE_BIZPROC_BTN_TIMELINE_TEXT') ?? ''))
			->setAction(
				(new JsEvent('Bizproc:Workflow:Timeline:Open'))
					->addActionParamString('workflowId', $workflowId)
					->setAnimation(Layout\Action\Animation::disableItem())
			)
		;
	}

	protected function createLogMenuItem(string $workflowId): ?Layout\Menu\MenuItem
	{
		if (!$this->isBizprocEnabled())
		{
			return null;
		}

		return (new Layout\Menu\MenuItem(Loc::getMessage('CRM_TIMELINE_BIZPROC_BTN_LOG_TEXT') ?? ''))
			->setAction(
				(new JsEvent('Bizproc:Workflow:Log'))
					->addActionParamString('workflowId', $workflowId)
					->setAnimation(Layout\Action\Animation::disableItem())
			)
		;
	}

	protected function buildProcessNameBlock(?string $templateName, ?string $workflowId): ?ContentBlock
	{
		if (empty($templateName) || empty($workflowId))
		{
			return null;
		}

		$textOrLink = ContentBlockFactory::createTextOrLink(
			$templateName,
			(new JsEvent('Bizproc:Workflow:Open'))
				->addActionParamString('workflowId', $workflowId)
		);

		return
			(new ContentBlockWithTitle())
				->setTitle(Loc::getMessage('CRM_TIMELINE_BIZPROC_CONTENT_PROCESS_TEXT'))
				->setContentBlock($textOrLink)
				->setInline()
		;
	}

	protected function createOpenButton(string $workflowId, $type = null)
	{
		if (empty($type))
		{
			$type = Button::TYPE_SECONDARY;
		}

		return (new Button(
			Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_BTN_OPEN_TEXT') ?? '',
			$type
		))
			->setAction((
			(new JsEvent('Bizproc:Workflow:Open'))
				->addActionParamString('workflowId', $workflowId)
			))
			->setHideIfReadonly()
		;
	}

	protected function createTimelineButton(string $workflowId)
	{
		return (new Button(
			Loc::getMessage('CRM_TIMELINE_BIZPROC_BTN_TIMELINE_TEXT') ?? '',
			Button::TYPE_SECONDARY
		))
			->setType(Button::TYPE_SECONDARY)
			->setAction((new JsEvent('Bizproc:Workflow:Timeline:Open'))
				->addActionParamString('workflowId', $workflowId)
				->setAnimation(Layout\Action\Animation::disableItem())
			)
			->setHideIfReadonly()
		;
	}

	protected function buildAssignedBlock(string $workflowId, array $users, $caption = null): ?ContentBlock
	{
		if (empty($workflowId) || empty($users))
		{
			return null;
		}

		$blocks = [];
		$userCnt = 0;
		foreach ($users as $key => $user)
		{
			if ($key + 1 > self::$usersLimit)
			{
				$userCnt++;
			}
			else
			{
				$url = new Uri("/company/personal/user/{$user['ID']}/");
				$blocks[$user['ID']] = (new ContentBlock\Link())
					->setValue($user['FULL_NAME'])
					->setAction(new Redirect($url))
				;
			}
		}

		if ($userCnt > 0)
		{
			$blocks[] = (new ContentBlock\Link())
				->setValue(Loc::getMessage('CRM_TIMELINE_BIZPROC_USERS_TASK_MORE', ['#NUM#' => $userCnt]))
				->setAction(
					(new JsEvent('Bizproc:Workflow:Timeline:Open'))
						->addActionParamString('workflowId', $workflowId)
						->setAnimation(Layout\Action\Animation::disableItem())
				)
			;
		}

		if (empty($blocks))
		{
			return null;
		}

		$usersInline = (new ContentBlock\LineOfTextBlocks())
			->setContentBlocks($blocks)
			->setDelimiter(', ')
		;

		return (new ContentBlockWithTitle())
			->setTitle($caption ?? Loc::getMessage('CRM_TIMELINE_BIZPROC_CONTENT_ASSIGNEE'))
			->setContentBlock($usersInline)
			->setInline()
		;
	}

	protected function buildTaskBlock(TaskBlockData $taskData): ?ContentBlock
	{
		if (empty($taskData->taskId) || empty($taskData->taskName))
		{
			return null;
		}

		$action =
			(new JsEvent('Bizproc:Task:Open'))
				->addActionParamString('taskId', $taskData->taskId)
		;
		if ($taskData->userId > 0)
		{
			$action->addActionParamInt('userId', $taskData->userId);
		}

		$textOrLink = ContentBlockFactory::createTextOrLink($taskData->taskName, $action);
		if ($taskData->rowLimit > 0)
		{
			$textOrLink->setRowLimit($taskData->rowLimit);
		}

		return (
		(new ContentBlockWithTitle())
			->setTitle(Loc::getMessage('CRM_TIMELINE_BIZPROC_CONTENT_TASK'))
			->setContentBlock($textOrLink)
			->setInline()
		);
	}

	protected function getWorkflowAuthor(string $workflowId): int
	{
		$workflow = WorkflowStateTable::getList([
			'filter' => [
				'=ID' => $workflowId,
			],
			'select' => ['STARTED_BY'],
		])->fetch();

		return self::resolveAuthorId($workflow);
	}

	protected function getUser(?int $userId): array
	{
		$result = \Bitrix\Main\UserTable::query()
			->setSelect(['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'WORK_POSITION', 'PERSONAL_PHOTO'])
			->setFilter(['ID' => $userId])
			->exec()
		;
		if ($user = $result->fetchObject())
		{
			return (new \Bitrix\Bizproc\UI\UserView($user))->jsonSerialize();
		}

		return [];
	}

	protected static function resolveAuthorId($fields): int
	{
		$authorId = 0;
		if (isset($fields['STARTED_BY']))
		{
			$authorId = (int)$fields['STARTED_BY'];
		}

		return $authorId;
	}

	protected function getEfficiencyData(string $workflowId): array
	{
		$result = WorkflowStateTable::getList([
			'filter' => ['=ID' => $workflowId],
			'select' => [
				'ID',
				'MODIFIED',
				'STARTED',
				'WORKFLOW_TEMPLATE_ID',
			],
		]);
		$settings = [];
		if ($row = $result->fetch())
		{
			$templateId = (int)$row['WORKFLOW_TEMPLATE_ID'];
			$executionTime = $row['MODIFIED']->getTimestamp() - $row['STARTED']->getTimestamp();
			$averageDuration = WorkflowDurationStatTable::getAverageDurationByTemplateId($templateId) ?? $executionTime;
			$settings['AVERAGE_DURATION'] = $averageDuration;
			$settings['EFFICIENCY'] = WorkflowDurationStatTable::getWorkflowEfficiency(
				$executionTime,
				$averageDuration
			);
			$settings['EXECUTION_TIME'] = $executionTime;
		}

		return $settings;
	}

	protected function isBizprocEnabled(): bool
	{
		static $result = null;

		if ($result === null)
		{
			$result = Loader::includeModule('bizproc');
		}

		return $result;
	}
}
