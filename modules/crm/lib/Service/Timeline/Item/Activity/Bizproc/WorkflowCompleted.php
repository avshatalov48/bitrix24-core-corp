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

		if (!$this->isBizprocEnabled())
		{
			return $result;
		}

		$averageDuration = $settings['AVERAGE_DURATION'] ?? null;
		$efficiency = $settings['EFFICIENCY'] ?? null;
		$executionTime = $settings['EXECUTION_TIME'] ?? null;
		$workflowAuthor = $settings['WORKFLOW_AUTHOR'] ?? [];
		$userId = $this->getContext()->getUserId();

		{ //TODO has a dependency on bizproc, delete after update
			$mobileConstant = \CBPViewHelper::class . '::MOBILE_CONTEXT';
			$mobileContext = defined($mobileConstant) ? \CBPViewHelper::MOBILE_CONTEXT : null;
		}

		$webResult = \CBPViewHelper::getWorkflowResult($workflowId, $userId) ?? [];
		if ($mobileContext)
		{
			$mobileResult = \CBPViewHelper::getWorkflowResult($workflowId, $userId, $mobileContext) ?? [];
		}

		if (empty($workflowAuthor))
		{
			$authorId = $this->getModel()->getAuthorId();
			$workflowAuthor = $this->getUser($authorId);
		}

		$result['workflowEfficiencyBlockWeb'] =
			(new Layout\Body\ContentBlock\WorkflowEfficiency())
				->setAverageDuration($averageDuration)
				->setEfficiency($efficiency)
				->setExecutionTime($executionTime)
				->setWorkflowResult($webResult)
				->setAuthor($workflowAuthor)
				->setScopeWeb()
		;

		if ($mobileContext)
		{
			$result['workflowEfficiencyBlockMobile'] =
				(new Layout\Body\ContentBlock\WorkflowEfficiency())
					->setAverageDuration($averageDuration)
					->setEfficiency($efficiency)
					->setExecutionTime($executionTime)
					->setWorkflowResult($mobileResult)
					->setAuthor($workflowAuthor)
					->setScopeMobile()
			;
		}

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
			'timeline' =>
				$this->createTimelineButton($workflowId)
					->setState(!$this->isBizprocEnabled() ? 'hidden' : null)
					->setScopeWeb()
			,
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

	public function getMenuItems(): array
	{
		$settings = $this->getActivityModel()?->get('SETTINGS');
		$workflowId = $settings['WORKFLOW_ID'] ?? null;
		if (empty($workflowId))
		{
			return [];
		}

		return [
			'log' => $this->createLogMenuItem($workflowId)?->setScopeWeb(),
			'timeline' => $this->createTimelineMenuItem($workflowId)?->setScopeMobile()
		];
	}
}
