<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\Block;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Main\Result;

class BaseBlockEvent extends AbstractBuilder
{
	protected ?int $entityTypeId = null;
	protected ?string $type = Dictionary::TYPE_CONTACT_CENTER;

	public static function createDefault(int $entityTypeId): static
	{
		$self = new static();
		$self->entityTypeId = $entityTypeId;

		return $self;
	}

	public function getType(): ?string
	{
		return $this->type;
	}

	public function setType(?string $type): self
	{
		$this->type = $type;

		return $this;
	}

	protected function getTool(): string
	{
		return Dictionary::TOOL_CRM;
	}

	protected function buildCustomData(): array
	{
		return [
			'category' => Dictionary::CATEGORY_KANBAN_OPERATIONS,
			'type' => $this->type,
		];
	}

	protected function customValidate(): Result
	{
		$result = new Result();

		if (!\CCrmOwnerType::IsDefined($this->entityTypeId))
		{
			return $result->addError(
				ErrorCode::getRequiredArgumentMissingError('entityTypeId'),
			);
		}

		return $result;
	}

	public function setEntityTypeId(int $entityTypeId): self
	{
		$this->entityTypeId = $entityTypeId;

		return $this;
	}
}