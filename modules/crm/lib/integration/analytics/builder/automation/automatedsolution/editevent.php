<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\Automation\AutomatedSolution;

use Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Main\Type\ArrayHelper;

final class EditEvent extends AbstractBuilder
{
	private ?int $id = null;
	private array $typeIds = [];

	protected function getTool(): string
	{
		return Dictionary::TOOL_CRM;
	}

	public function setId(int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function setTypeIds(array $typeIds): self
	{
		ArrayHelper::normalizeArrayValuesByInt($typeIds);

		$this->typeIds = $typeIds;

		return $this;
	}

	protected function buildCustomData(): array
	{
		if ($this->id > 0)
		{
			$this->setP2('id', $this->id);
		}
		if ($this->typeIds)
		{
			$this->setP4('typeIds', implode(',', $this->typeIds));
		}

		return [
			'category' => Dictionary::CATEGORY_AUTOMATION_OPERATIONS,
			'event' => Dictionary::EVENT_AUTOMATION_EDIT,
			'type' => Dictionary::TYPE_AUTOMATED_SOLUTION,
		];
	}
}
