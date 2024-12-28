<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Bizproc;

use Bitrix\Crm\Service\Timeline\Item\Bizproc\Workflow;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

class CommentRead extends LogMessage
{
	use Workflow;

	public function getType(): string
	{
		return 'BizprocCommentRead';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_LOG_MESSAGE_BIZPROC_COMMENT_READ');
	}

	public function getIconCode(): ?string
	{
		return Icon::MESSAGE_WITH_POINT;
	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$settings = $this->getModel()->getSettings();
		$workflowId = $settings['WORKFLOW_ID'] ?? null;
		$templateName = $settings['WORKFLOW_TEMPLATE_NAME'] ?? null;

		$processNameBlock = $this->buildProcessNameBlock($templateName, $workflowId);
		if (isset($processNameBlock))
		{
			$result['processNameBlock'] = $processNameBlock;
		}

		return $result;
	}

	public function getTags(): array
	{
		return [
			'status' => $this->getStatusBlock(),
		];
	}

	private function getStatusBlock(): Tag
	{
		return new Tag(
			Loc::getMessage('CRM_LOG_MESSAGE_BIZPROC_COMMENT_READ_STATUS') ?? '',
			Tag::TYPE_SECONDARY
		);
	}
}