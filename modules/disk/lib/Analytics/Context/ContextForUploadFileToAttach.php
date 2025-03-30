<?php

declare(strict_types=1);

namespace Bitrix\Disk\Analytics\Context;

use Bitrix\Crm\Integration\Disk\CommentConnector;
use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Uf\BlogPostCommentConnector;
use Bitrix\Disk\Uf\BlogPostConnector;
use Bitrix\Disk\Uf\CrmCompanyConnector;
use Bitrix\Disk\Uf\CrmConnector;
use Bitrix\Disk\Uf\CrmContactConnector;
use Bitrix\Disk\Uf\CrmDealConnector;
use Bitrix\Disk\Uf\CrmLeadConnector;
use Bitrix\Disk\Uf\ForumMessageConnector;
use Bitrix\Forum\MessageTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration\Disk\Connector\CheckList\Task as TaskCheckList;
use Bitrix\Tasks\Integration\Disk\Connector\Task;

class ContextForUploadFileToAttach implements SectionStrategyInterface, ElementStrategyInterface
{
	public function __construct(
		private readonly AttachedObject $attachedObject,
	)
	{
	}

	public function getSection(): string
	{
		$moduleId = $this->attachedObject->getModuleId();
		return match ($moduleId) {
			'blog' => 'feed',
			'forum' => $this->getForumMessageSection(),
			default => $moduleId,
		};
	}

	public function getSubSection(): string
	{
		$entityType = $this->attachedObject->getEntityType();

		return match ($entityType) {
			Task::class, TaskCheckList::class => 'task_card',
			ForumMessageConnector::class => $this->getForumMessageSubsection(),
			BlogPostConnector::class => 'post',
			BlogPostCommentConnector::class => 'post_comment',
			CommentConnector::class => $this->getCrmCommentSubsection(),
			default => '',
		};
	}

	private function getForumMessageSection(): string
	{
		return $this->isForumMessageBelongToTasks() ? 'tasks' : 'forum';
	}
	
	private function getForumMessageSubsection(): string
	{
		return $this->isForumMessageBelongToTasks() ? 'task_comment' : '';
	}

	private function isForumMessageBelongToTasks(): bool
	{
		if (Loader::includeModule('forum'))
		{
			$message = MessageTable::getById($this->attachedObject->getEntityId())->fetch();

			if ($message !== false)
			{
				$taskForumId = (int)Option::get('tasks', 'task_forum_id');

				if ($taskForumId === (int)$message['FORUM_ID'])
				{
					return true;
				}
			}
		}

		return false;
	}

	public function getElement(): string
	{
		$entityType = $this->attachedObject->getEntityType();

		return match ($entityType) {
			TaskCheckList::class => 'checklist',
			default => '',
		};
	}

	private function getCrmCommentSubsection(): string
	{
		$commentConnector = $this->attachedObject->getConnector();

		// todo: remove method_exists when it is no longer needed
		if (($commentConnector instanceof CommentConnector) && method_exists($commentConnector, 'getCrmConnector'))
		{
			$crmConnector = $commentConnector->getCrmConnector();

			return $this->getCrmSubSection($crmConnector);
		}

		return '';
	}

	private function getCrmSubSection(?CrmConnector $crmConnector): string
	{
		return match (true) {
			$crmConnector instanceof CrmDealConnector => 'deal',
			$crmConnector instanceof CrmLeadConnector => 'lead',
			$crmConnector instanceof CrmCompanyConnector => 'company',
			$crmConnector instanceof CrmContactConnector => 'contact',
			default => '',
		};
	}

}