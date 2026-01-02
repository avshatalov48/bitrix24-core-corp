<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Field;

final class StageField extends AbstractField
{
	public function __construct(
		public string $title,
		public string $stageName,
		public string $stageColor,
	)
	{
	}

	public function getName(): string
	{
		return 'StageField';
	}

	public function getProps(): array
	{
		return [
			'title' => $this->title,
			'stage' => [
				'name' => $this->stageName,
				'color' => $this->stageColor,
			],
		];
	}
}
