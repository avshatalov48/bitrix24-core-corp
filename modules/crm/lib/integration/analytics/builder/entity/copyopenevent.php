<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\Entity;

use Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Main\Result;

final class CopyOpenEvent extends AbstractBuilder
{
	private ?int $entityTypeId = null;

	public static function createDefault(int $entityTypeId): self
	{
		$self = new self();
		$self->entityTypeId = $entityTypeId;

		return $self;
	}

	protected function getTool(): string
	{
		return Dictionary::TOOL_CRM;
	}

	protected function customValidate(): Result
	{
		$result = new Result();

		if (!\CCrmOwnerType::IsDefined($this->entityTypeId))
		{
			return $result->addError(
				\Bitrix\Crm\Controller\ErrorCode::getRequiredArgumentMissingError('entityTypeId'),
			);
		}

		return $result;
	}

	protected function buildCustomData(): array
	{
		return [
			'category' => Dictionary::CATEGORY_ENTITY_OPERATIONS,
			'event' => Dictionary::EVENT_ENTITY_COPY_OPEN,
			'type' => Dictionary::getAnalyticsEntityType($this->entityTypeId),
		];
	}

	public function setEntityTypeId(int $entityTypeId): self
	{
		$this->entityTypeId = $entityTypeId;

		return $this;
	}
}
