<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\Entity;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class ConvertEvent extends AbstractBuilder
{
	private ?int $dstEntityTypeId = null;
	private ?int $srcEntityTypeId = null;

	public static function createDefault(int $dstEntityTypeId): self
	{
		$self = new self();
		$self->dstEntityTypeId = $dstEntityTypeId;

		return $self;
	}

	protected function getTool(): string
	{
		return Dictionary::TOOL_CRM;
	}

	protected function buildCustomData(): array
	{
		if ($this->srcEntityTypeId)
		{
			$this->setP2WithValueNormalization('from', Dictionary::getAnalyticsEntityType($this->srcEntityTypeId));
		}

		return [
			'category' => Dictionary::CATEGORY_ENTITY_OPERATIONS,
			'event' => Dictionary::EVENT_ENTITY_CONVERT,
			'type' => Dictionary::getAnalyticsEntityType($this->dstEntityTypeId),
		];
	}

	protected function customValidate(): Result
	{
		$result = new Result();

		if (!\CCrmOwnerType::IsDefined($this->dstEntityTypeId))
		{
			return $result->addError(
				ErrorCode::getRequiredArgumentMissingError('dstEntityTypeId'),
			);
		}

		if ($this->srcEntityTypeId && !\CCrmOwnerType::IsDefined($this->srcEntityTypeId))
		{
			return $result->addError(new Error('Unknown src entity type', ErrorCode::INVALID_ARG_VALUE));
		}

		return $result;
	}

	public function setDstEntityTypeId(int $dstEntityTypeId): self
	{
		$this->dstEntityTypeId = $dstEntityTypeId;

		return $this;
	}

	public function setSrcEntityTypeId(?int $srcEntityTypeId): self
	{
		$this->srcEntityTypeId = $srcEntityTypeId;

		return $this;
	}
}
