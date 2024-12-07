<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\Automation\Type;

use Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder;
use Bitrix\Crm\Integration\Analytics\Dictionary;

final class CreateEvent extends AbstractBuilder
{
	private ?int $id = null;
	private ?string $presetId = null;

	protected function getTool(): string
	{
		return Dictionary::TOOL_CRM;
	}

	public function setId(int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function setPresetId(string $preset): self
	{
		$this->presetId = $preset;

		return $this;
	}

	protected function buildCustomData(): array
	{
		if ($this->id > 0)
		{
			$this->setP2('id', $this->id);
		}
		if ($this->presetId)
		{
			$this->setP4('preset', $this->presetId);
		}

		return [
			'category' => Dictionary::CATEGORY_AUTOMATION_OPERATIONS,
			'event' => Dictionary::EVENT_AUTOMATION_CREATE,
			'type' => Dictionary::TYPE_DYNAMIC,
		];
	}
}
