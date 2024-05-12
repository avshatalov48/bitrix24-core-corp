<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\AI;

use Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

abstract class AIBaseEvent extends AbstractBuilder
{
	private string $type = Dictionary::TYPE_MANUAL;

	private ?DateTime $createdTime = null;
	private ?DateTime $finishedTime = null;
	private ?int $operationTypeId = null;
	private ?int $activityOwnerTypeId = null;
	private ?int $activityId = null;

	final public function __construct()
	{
		$this->setSection(Dictionary::SECTION_CRM);
	}

	final protected function getTool(): string
	{
		return Dictionary::TOOL_AI;
	}

	final protected function customValidate(): Result
	{
		$result = new Result();
		if ($this->getSection() !== Dictionary::SECTION_CRM)
		{
			$result->addError(
				new Error(
					'c_section should be crm',
					[
						'allowed' => [Dictionary::SECTION_CRM],
					]
				)
			);
		}

		if (!$this->createdTime || !$this->finishedTime)
		{
			$result->addError(
				new Error('createdTime and finishedTime are required'),
			);
		}

		if ($this->operationTypeId <= 0)
		{
			$result->addError(
				new Error('operation type is required'),
			);
		}

		if (!\CCrmOwnerType::IsDefined($this->activityOwnerTypeId))
		{
			$result->addError(
				new Error(
					'activity owner type id is required',
				)
			);
		}

		if ($this->activityId <= 0)
		{
			$result->addError(
				new Error('activity id is required')
			);
		}

		return $result;
	}

	final protected function buildCustomData(): array
	{
		$this->setP2('stepNumber', (string)$this->operationTypeId);

		$durationInSeconds = $this->finishedTime->getTimestamp() - $this->createdTime->getTimestamp();
		$this->setP4('stepDuration', $durationInSeconds);

		$this->setSubSection(Dictionary::getAnalyticsEntityType($this->activityOwnerTypeId));
		$this->setP5('idCall', (string)$this->activityId);

		return [
			'type' => $this->type,
			'category' => Dictionary::CATEGORY_CRM_OPERATIONS,
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
