<?php

namespace Bitrix\Crm\Service\Timeline\Item\Bizproc;

use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

final class WorkflowTerminated extends Base
{
	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_BIZPROC_TERMINATED_TITLE');
	}

	protected function getBizprocTypeId(): string
	{
		return 'WorkflowTerminated';
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
			'Status' => $this->getStatusBlock(),
		];
	}

	private function getStatusBlock(): Tag
	{
		return new Tag(
			Loc::getMessage('CRM_TIMELINE_BIZPROC_TERMINATED_TEXT') ?? '',
			Tag::TYPE_SECONDARY
		);
	}
}