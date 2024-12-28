<?php

namespace Bitrix\Mobile\AvaMenu\Profile\Type;

class Collaber extends BaseType
{

	public function getAccentType(): string
	{
		return 'green';
	}

	public function getStyle(): ?array
	{
		return [
			'titleColor' => 'collabElement1',
			'backgroundColor' => 'collabBgContent1',
		];
	}

	public function getPlaceholderBackgroundColor(): string
	{
		return 'collabAccentPrimary';
	}
}
