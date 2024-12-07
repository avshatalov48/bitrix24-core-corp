<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\AI;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use CCrmActivityDirection;
use CCrmOwnerType;

final class CallActivityWithAudioRecordingEvent extends AbstractBuilder
{
	private string $tool = Dictionary::TOOL_AI;
	private string $category = Dictionary::CATEGORY_CRM_OPERATIONS;

	private ?int $activityOwnerTypeId = null;
	private ?int $activityId = null;
	private ?int $activityDirection = null;
	private ?int $callDuration = null;

	public function setTool(string $tool): self
	{
		$this->tool = $tool;

		return $this;
	}

	public function setCategory(string $category): self
	{
		$this->category = $category;

		return $this;
	}

	protected function getTool(): string
	{
		return $this->tool;
	}

	protected function customValidate(): Result
	{
		$result = new Result();

		if (!CCrmOwnerType::IsDefined($this->activityOwnerTypeId))
		{
			$result->addError(
				ErrorCode::getRequiredArgumentMissingError('activityOwnerTypeId')
			);
		}

		if ($this->activityId <= 0)
		{
			$result->addError(
				ErrorCode::getRequiredArgumentMissingError('activityId'),
			);
		}

		if ($this->callDuration === null)
		{
			$result->addError(
				ErrorCode::getRequiredArgumentMissingError('callDuration'),
			);
		}

		if ($this->activityDirection !== null && !CCrmActivityDirection::IsDefined($this->activityDirection))
		{
			$result->addError(
				new Error('Unknown activity direction', ErrorCode::INVALID_ARG_VALUE),
			);
		}

		return $result;
	}

	protected function buildCustomData(): array
	{
		$this->setSection(Dictionary::SECTION_CRM);
		$this->setSubSection(Dictionary::getAnalyticsEntityType($this->activityOwnerTypeId));

		$this->setP4('callDuration', (string)$this->callDuration);
		$this->setP5('idCall', (string)$this->activityId);

		return [
			'category' => $this->category,
			'event' => Dictionary::EVENT_CALL_ACTIVITY_WITH_AUDIO_RECORDING,
			'type' => CCrmActivityDirection::IsDefined($this->activityDirection)
				? mb_strtolower(CCrmActivityDirection::ResolveName($this->activityDirection))
				: null
			,
		];
	}

	public function setActivityOwnerTypeId(int $entityTypeId): self
	{
		$this->activityOwnerTypeId = $entityTypeId;

		return $this;
	}

	public function setActivityId(int $activityId): self
	{
		$this->activityId = $activityId;

		return $this;
	}

	public function setActivityDirection(int $directionTypeId): self
	{
		$this->activityDirection = $directionTypeId;

		return $this;
	}

	public function setCallDuration(int $duration): self
	{
		$this->callDuration = $duration;

		return $this;
	}
}
