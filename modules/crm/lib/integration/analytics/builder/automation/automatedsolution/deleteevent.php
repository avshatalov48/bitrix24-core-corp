<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\Automation\AutomatedSolution;

use Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder;
use Bitrix\Crm\Integration\Analytics\Dictionary;

final class DeleteEvent extends AbstractBuilder
{
	private ?int $id = null;

	protected function getTool(): string
	{
		return Dictionary::TOOL_CRM;
	}

	public function setId(int $id): self
	{
		$this->id = $id;

		return $this;
	}

	protected function buildCustomData(): array
	{
		if ($this->id > 0)
		{
			$this->setP2('id', $this->id);
		}

		return [
			'category' => Dictionary::CATEGORY_AUTOMATION_OPERATIONS,
			'event' => Dictionary::EVENT_AUTOMATION_DELETE,
			'type' => Dictionary::TYPE_AUTOMATED_SOLUTION,
		];
	}
}
