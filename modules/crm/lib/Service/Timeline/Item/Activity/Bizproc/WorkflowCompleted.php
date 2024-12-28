<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\Bizproc;

use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

class WorkflowCompleted extends Base
{
	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_TITLE');
	}

	protected function getActivityTypeId(): string
	{
		return 'BizprocWorkflowCompleted';
	}

	protected function isBuiltOnlyForCurrentUser(): bool
	{
		return true;
	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$settings = $this->getActivitySettings();
		$workflowId = $settings['WORKFLOW_ID'] ?? null;
		$templateName = $settings['WORKFLOW_TEMPLATE_NAME'] ?? null;

		if (empty($workflowId) || empty($templateName))
		{
			return null;
		}

		$processNameBlock = $this->buildProcessNameBlock($templateName, $workflowId);
		if (isset($processNameBlock))
		{
			$result['processNameBlock'] = $processNameBlock;
		}

		$averageDuration = $settings['AVERAGE_DURATION'] ?? null;
		$efficiency = $settings['EFFICIENCY'] ?? null;
		$executionTime = $settings['EXECUTION_TIME'] ?? null;
		$workflowAuthor = $settings['WORKFLOW_AUTHOR'] ?? [];
		$workflowResult = \CBPViewHelper::getWorkflowResult($workflowId, $this->getContext()->getUserId()) ?? [];

		if (empty($workflowResult) && empty($workflowAuthor))
		{
			$authorId = $this->getModel()->getAuthorId();
			$workflowAuthor = $this->getUser($authorId);
		}

		$result['workflowEfficiencyBlock'] =
			(new Layout\Body\ContentBlock\WorkflowEfficiency())
				->setAverageDuration($averageDuration)
				->setEfficiency($efficiency)
				->setExecutionTime($executionTime)
				->setWorkflowResult($workflowResult)
				->setAuthor($workflowAuthor)
		;

		return $result;
	}

	public function getButtons(): ?array
	{
		$settings = $this->getActivitySettings();
		$workflowId = $settings['WORKFLOW_ID'] ?? null;
		if (empty($workflowId))
		{
			return [];
		}

		$isScheduled = $this->getModel()->isScheduled();
		$btnType = $isScheduled ? Button::TYPE_PRIMARY : Button::TYPE_SECONDARY;

		return [
			'open' => $this->createOpenButton($workflowId, $btnType),
			'timeline' => $this->createTimelineButton($workflowId),
		];
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
			Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_COMPLETED') ?? '',
			Tag::TYPE_SUCCESS
		);
	}
}
