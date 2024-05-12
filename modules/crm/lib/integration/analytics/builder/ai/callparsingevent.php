<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\AI;

use Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

/**
 * It has a bit of copy-paste from
 * @see AIBaseEvent
 *
 * If you add more copy-paste here, consider refactoring it
 */
final class CallParsingEvent extends AbstractBuilder
{
	private string $type = Dictionary::TYPE_MANUAL;

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
		$this->setSubSection(Dictionary::getAnalyticsEntityType($this->activityOwnerTypeId));
		$this->setP5('idCall', (string)$this->activityId);

		return [
			'type' => $this->type,
			'category' => Dictionary::CATEGORY_CRM_OPERATIONS,
			'event' => Dictionary::EVENT_CALL_PARSING,
		];
	}

	final public function setIsManualLaunch(bool $isManualLaunch): self
	{
		$this->type = $isManualLaunch ? Dictionary::TYPE_MANUAL : Dictionary::TYPE_AUTO;

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
