<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\Tasks;

use Bitrix\Crm\Integration\Tasks\TaskCounter;
use Bitrix\Crm\Integration\Tasks\TaskObject;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Item\AssociatedEntityModel;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Date;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Link;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Common\Logo;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

class Comment extends Activity
{
	use ActivityTrait;

	private const ACTIVITY_TYPE_ID = 'TasksTaskComment';

	protected function getActivityTypeId(): string
	{
		return self::ACTIVITY_TYPE_ID;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('TASKS_TASK_COMMENT_DEAL_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Icon::COMMENT;
	}

	public function getMenuItems(): array
	{
		return [];
	}

	public function getContentBlocks(): ?array
	{
		$model = $this->getAssociatedEntityModel();
		$task = $this->getTask($model);
		if (is_null($task))
		{
			return null;
		}

		$blocks = [
			'Task' => $this->getTaskBlock($model),
			'From' => $this->getFromBlock($model),
			'Updated' => $this->getUpdatedBlock($model, $task),
		];

		$files = $this->getFiles();

		if (!empty($files))
		{
			$blocks['Files'] = $this->getFilesBlock($files);
		}

		return $blocks;
	}

	public function getButtons(): ?array
	{
		$model = $this->getAssociatedEntityModel();
		$data = $model->get('SETTINGS');
		$task = TaskObject::getObject($data['TASK_ID'], true);
		if (is_null($task))
		{
			return null;
		}

		return [
			'Open' => $this->getOpenButton($model, $task)
		];
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	public function getOpenButton(AssociatedEntityModel $model, \Bitrix\Tasks\Internals\TaskObject $task): Button
	{
		$openButtonObject = new Button(
			Loc::getMessage('TASKS_TASK_COMMENT_DEAL_BUTTON_OPEN'),
			Button::TYPE_PRIMARY
		);

		$openButtonObject->setAction($this->getTaskAction($task, 'view_button'));

		return $openButtonObject;
	}

	public function getLogo(): Layout\Body\Logo
	{
		return (new Logo(Logo::UNREAD_COMMENT))->createLogo();
	}

	public function getTaskBlock(AssociatedEntityModel $model): ContentBlockWithTitle
	{
		$data = $model->get('SETTINGS');
		$task = TaskObject::getObject($data['TASK_ID']);
		$taskBlockObject = new ContentBlockWithTitle();
		$taskBlockObject
			->setInline()
			->setTitle(Loc::getMessage('TASKS_TASK_COMMENT_DEAL_TASK'))
			->setContentBlock(
				(new Link())
					->setValue($task->getTitle())
					->setAction($this->getTaskAction($task))
			);

		return $taskBlockObject;
	}

	public function getFromBlock(AssociatedEntityModel $model): ContentBlockWithTitle
	{
		$fromBlockObject = new ContentBlockWithTitle();
		$fromBlockObject
			->setInline()
			->setTitle(Loc::getMessage('TASKS_TASK_COMMENT_DEAL_FROM'))
			->setContentBlock(
				(new Link())
					->setValue($this->getUserName($model))
					->setAction($this->getUserAction($model))
					->setIsBold(true)
			);

		return $fromBlockObject;
	}

	public function getUpdatedBlock(AssociatedEntityModel $model, \Bitrix\Tasks\Internals\TaskObject $task)
	{
		$data = $model->get('SETTINGS');
		$responsibleId = $model->get('RESPONSIBLE_ID');
		$lastCommentDate = $data['LAST_COMMENT_DATE'] ?? time();
		$unreadCommentsCount = TaskCounter::getCommentsCount($task->getId(), $responsibleId);

		$unreadCommentsCountBlock = new Text();
		$unreadCommentsCountBlock->setValue($unreadCommentsCount);

		$lastCommentDateBlock = new Date();
		$lastCommentDateBlock->setDate(DateTime::createFromTimestamp($lastCommentDate));

		$contentBlock = ContentBlockFactory::createLineOfTextFromTemplate($this->getTemplate($unreadCommentsCount), [
				'#COMMENT_COUNT#' => $unreadCommentsCountBlock,
				'#DATE_TIME#' => $lastCommentDateBlock,
			]
		);

		$updatedBlockObject = new ContentBlockWithTitle();
		$updatedBlockObject
			->setInline()
			->setTitle(Loc::getMessage('TASKS_TASK_COMMENT_DEAL_UPDATED'))
			->setContentBlock($contentBlock)
		;

		return $updatedBlockObject;
	}

	public function getFilesBlock(array $files): Layout\Body\ContentBlock\FileList
	{
		$filesBlockObject = new Layout\Body\ContentBlock\FileList();
		$filesBlockObject
			->setFiles($files)
			->setTitle(Loc::getMessage('TASKS_TASK_COMMENT_DEAL_FILES'))
		;

		return $filesBlockObject;
	}

	public function getTemplate(int $unreadCommentsCount): string
	{
		$form = Loc::getPluralForm($unreadCommentsCount);

		return Loc::getMessage("TASKS_TASK_COMMENT_DEAL_COMMENT_COUNT_PLURAL_{$form}");
	}

	protected function getCompleteAction(): Layout\Action\RunAjaxAction
	{
		return (new Layout\Action\RunAjaxAction('crm.timeline.activity.complete'))
			->addActionParamInt('activityId', $this->getActivityId())
			->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
			->addActionParamInt('ownerId', $this->getContext()->getEntityId())
			->setAnimation(Layout\Action\Animation::disableItem()->setForever())
		;
	}
}