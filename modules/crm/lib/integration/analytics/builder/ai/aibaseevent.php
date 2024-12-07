<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\AI;

use Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use CCrmOwnerType;

abstract class AIBaseEvent extends AbstractBuilder
{
	private string $tool = Dictionary::TOOL_AI;
	private string $category = Dictionary::CATEGORY_CRM_OPERATIONS;
	private string $type = Dictionary::TYPE_MANUAL;

	private ?DateTime $createdTime = null;
	private ?DateTime $finishedTime = null;
	private ?int $operationTypeId = null;
	private ?int $activityOwnerTypeId = null;
	private ?int $activityId = null;

	final public function setTool(string $tool): self
	{
		$this->tool = $tool;

		return $this;
	}

	final public function setCategory(string $category): self
	{
		$this->category = $category;

		return $this;
	}

	final protected function getTool(): string
	{
		return $this->tool;
	}

	final protected function customValidate(): Result
	{
		$result = new Result();

		if (!$this->createdTime || !$this->finishedTime)
		{
			$result->addError(
				\Bitrix\Crm\Controller\ErrorCode::getRequiredArgumentMissingError('createdTime || finishedTime'),
			);
		}

		if ($this->operationTypeId <= 0)
		{
			$result->addError(
				\Bitrix\Crm\Controller\ErrorCode::getRequiredArgumentMissingError('operationTypeId'),
			);
		}

		if (!CCrmOwnerType::IsDefined($this->activityOwnerTypeId))
		{
			$result->addError(
				\Bitrix\Crm\Controller\ErrorCode::getRequiredArgumentMissingError('activityOwnerTypeId'),
			);
		}

		if ($this->activityId <= 0)
		{
			$result->addError(
				\Bitrix\Crm\Controller\ErrorCode::getRequiredArgumentMissingError('activityId'),
			);
		}

		return $result;
	}

	final protected function buildCustomData(): array
	{
		$this->setSection(Dictionary::SECTION_CRM);

		$this->setP2('stepNumber', (string)$this->operationTypeId);

		$durationInSeconds = $this->finishedTime->getTimestamp() - $this->createdTime->getTimestamp();
		$this->setP4('stepDuration', (string)$durationInSeconds);

		$this->setSubSection(Dictionary::getAnalyticsEntityType($this->activityOwnerTypeId));
		$this->setP5('idCall', (string)$this->activityId);

		return [
			'type' => $this->type,
			'category' => $this->category,
			'event' => $this->getEvent(),
		];
	}

	abstract protected function getEvent(): string;

	final public function setIsManualLaunch(bool $isManualLaunch): self
	{
		$this->type = $isManualLaunch ? Dictionary::TYPE_MANUAL : Dictionary::TYPE_AUTO;

		return $this;
	}

	final public function setCreatedTime(DateTime $createdTime): self
	{
		$this->createdTime = $createdTime;

		return $this;
	}

	final public function setFinishedTime(DateTime $finishedTime): self
	{
		$this->finishedTime = $finishedTime;

		return $this;
	}

	final public function setOperationType(int $operationTypeId): self
	{
		$this->operationTypeId = $operationTypeId;

		return $this;
	}

	final public function setActivityOwnerTypeId(int $entityTypeId): self
	{
		$this->activityOwnerTypeId = $entityTypeId;

		return $this;
	}

	final public function setActivityId(int $activityId): self
	{
		$this->activityId = $activityId;

		return $this;
	}
}
