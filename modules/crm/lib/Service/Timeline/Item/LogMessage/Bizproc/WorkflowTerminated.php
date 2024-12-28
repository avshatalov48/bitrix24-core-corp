<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Bizproc;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Item\Bizproc\Workflow;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

class WorkflowTerminated extends LogMessage
{
	use Workflow;

	public function getType(): string
	{
		return 'BizprocWorkflowTerminated';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_LOG_MESSAGE_BIZPROC_WORKFLOW_TERMINATED_TITLE');
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

	public function getTags(): ?array
	{
		return [
			'status' => $this->getStatusBlock(),
		];
	}

	private function getStatusBlock(): Tag
	{
		return new Tag(
			Loc::getMessage('CRM_LOG_MESSAGE_BIZPROC_WORKFLOW_TERMINATED_STATUS') ?? '',
			Tag::TYPE_SECONDARY
		);
	}
}