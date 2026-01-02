<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\Activity;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Main\Result;
use CCrmOwnerType;

abstract class ActivityBaseEvent extends AbstractBuilder
{
	protected ?int $entityTypeId = null;
	protected ?string $type = null;

	abstract protected function getEvent(): string;
	public static function createDefault(int $entityTypeId): static
	{
		$self = new static();

		$self->entityTypeId = $entityTypeId;

		return $self;
	}

	final protected function getTool(): string
	{
		return Dictionary::TOOL_CRM;
	}

	final public function setEntityTypeId(int $entityTypeId): self
	{
		$this->entityTypeId = $entityTypeId;

		return $this;
	}

	final public function getType(): ?string
	{
		return $this->type;
	}

	final public function setType(?string $type): self
	{
		$this->type = $type;

		return $this;
	}

	final protected function buildCustomData(): array
	{
		$this->setSection(Dictionary::getAnalyticsEntityType($this->entityTypeId) . '_section');

		return [
			'category' => Dictionary::CATEGORY_ACTIVITY_OPERATIONS,
			'type' => $this->getType(),
			'event' => $this->getEvent(),
		];
	}

	final protected function customValidate(): Result
	{
		$result = new Result();

		if (!CCrmOwnerType::IsDefined($this->entityTypeId))
		{
			return $result->addError(
				ErrorCode::getRequiredArgumentMissingError('entityTypeId'),
			);
		}

		if (empty($this->getType()))
		{
			return $result->addError(
				ErrorCode::getRequiredArgumentMissingError('type'),
			);
		}

		return $result;
	}
}
