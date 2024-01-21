<?php

namespace Bitrix\Mobile\Field\Type;

class IblockSectionField extends IblockElementField
{
	public const TYPE = 'iblock_section';

	protected function getSelectorType(): string
	{
		return 'iblock-section-user-field';
	}

	public function getBoundEntities(): array
	{
		$value = $this->value;
		if (!$value)
		{
			return [];
		}

		if (!$this->isMultiple())
		{
			$value = [$value];
		}

		return [
			'iblock_section' => [
				'ids' => $value,
				'field' => $this,
			],
		];
	}
}
