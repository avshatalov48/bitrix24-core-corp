<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Mixin\Actionable;
use Bitrix\Main\Localization\Loc;

class WorkflowEfficiency extends ContentBlock
{
	use Actionable;

	protected ?int $averageDuration = null;
	protected ?string $efficiency = null;
	protected ?int $executionTime = null;
	protected array $workflowResult = [];
	protected array $author = [];

	public function getRendererName(): string
	{
		return 'WorkflowEfficiency';
	}

	public function getAverageDuration(): ?int
	{
		return $this->averageDuration;
	}

	public function getEfficiency(): ?string
	{
		return $this->efficiency;
	}

	public function getExecutionTime(): ?int
	{
		return $this->executionTime;
	}

	public function getWorkflowResult(): array
	{
		return $this->workflowResult;
	}

	public function getAuthor(): array
	{
		return $this->author;
	}

	public function setAverageDuration($averageDuration)
	{
		$this->averageDuration = $averageDuration;

		return $this;
	}

	public function setEfficiency($efficiency)
	{
		$this->efficiency = $efficiency;

		return $this;
	}

	public function setExecutionTime($executionTime)
	{
		$this->executionTime = $executionTime;

		return $this;
	}

	public function setWorkflowResult(array $result)
	{
		$this->workflowResult = $result;

		return $this;
	}

	public function setAuthor(array $user)
	{
		$this->author = $user;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'averageDuration' => $this->getAverageDuration(),
			'efficiency' => html_entity_decode($this->getEfficiency()),
			'executionTime' => $this->getExecutionTime(),
			'processTimeText' => Loc::getMessage('CRM_WORKFLOW_EFFICIENCY_PROCESS_TIME'),
			'workflowResult' => $this->getWorkflowResult(),
			'author' => $this->getAuthor(),
		];
	}
}