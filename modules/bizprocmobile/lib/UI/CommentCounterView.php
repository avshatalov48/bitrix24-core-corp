<?php

namespace Bitrix\BizprocMobile\UI;

use Bitrix\Bizproc\Workflow\Entity\WorkflowUserCommentTable;
use Bitrix\Main\Loader;

class CommentCounterView implements \JsonSerializable
{
	private string $workflowId;
	private int $userId;

	public function __construct(string $workflowId, int $userId)
	{
		$this->workflowId = $workflowId;
		$this->userId = $userId;
	}

	public function jsonSerialize(): array
	{
		return [
			'new' => $this->getNewCommentsCount(),
			'all' => $this->getCommentsCount(),
		];
	}

	private function getNewCommentsCount(): int
	{
		$row = WorkflowUserCommentTable::query()
			->setSelect(['UNREAD_CNT'])
			->where('WORKFLOW_ID', $this->workflowId)
			->where('USER_ID', $this->userId)
			->fetch();

		return (int)($row['UNREAD_CNT'] ?? 0);
	}

	private function getCommentsCount(): int
	{
		if (!Loader::includeModule('forum'))
		{
			return 0;
		}

		$topic = \CForumTopic::getList([], ['XML_ID' => 'WF_' . $this->workflowId])->fetch() ?: [];

		return (int)($topic['POSTS'] ?? 0);
	}
}
