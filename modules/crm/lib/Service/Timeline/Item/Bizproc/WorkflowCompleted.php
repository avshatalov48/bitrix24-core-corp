<?php

namespace Bitrix\Crm\Service\Timeline\Item\Bizproc;

use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Common;
use Bitrix\Crm\Service\Timeline\Layout\Body;

final class WorkflowCompleted extends Base
{
	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_BIZPROC_COMPLETED_TITLE');
	}

	protected function getBizprocTypeId(): string
	{
		return 'WorkflowCompleted';
	}

	protected function isBuiltOnlyForCurrentUser(): bool
	{
		return true;
	}

	public function getLogo(): ?Body\Logo
	{
		return Common\Logo::getInstance(Common\Logo::BIZPROC)
			->createLogo()
			?->setInCircle()
			?->setAdditionalIconCode('check')
			?->setAdditionalIconType(Body\Logo::ICON_TYPE_SUCCESS)
		;
	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$settings = $this->getModel()->getSettings();
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

		if (empty($efficiency))
		{
			$settings = $this->getEfficiencyData($workflowId);
			$averageDuration = $settings['AVERAGE_DURATION'] ?? null;
			$efficiency = $settings['EFFICIENCY'] ?? null;
			$executionTime = $settings['EXECUTION_TIME'] ?? null;
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

	public function getMenuItems(): array
	{
		$settings = $this->getModel()->getSettings();
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

	public function getTags(): ?array
	{
		return [
			'Status' => $this->getStatusBlock(),
		];
	}

	private function getStatusBlock(): Tag
	{
		return new Tag(
			Loc::getMessage('CRM_TIMELINE_BIZPROC_COMPLETED_CAPTION') ?? '',
			Tag::TYPE_SUCCESS
		);
	}

	public function getButtons(): array
	{
		$settings = $this->getModel()->getSettings();
		$workflowId = $settings['WORKFLOW_ID'] ?? null;
		if (empty($workflowId))
		{
			return [];
		}

		return [
			'open' => $this->createOpenButton($workflowId),
			'timeline' =>
				$this->createTimelineButton($workflowId)
					->setState(!$this->isBizprocEnabled() ? 'hidden' : null)
					->setScopeWeb()
			,
		];
	}
}