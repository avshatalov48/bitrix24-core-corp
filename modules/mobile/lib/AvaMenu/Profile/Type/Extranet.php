<?php

namespace Bitrix\Mobile\AvaMenu\Profile\Type;

class Extranet extends BaseType
{
	public function getAccentType(): string
	{
		return 'orange';
	}

	public function getStyle(): ?array
	{
		return [
			'titleColor' => 'accentExtraOrange',
			'backgroundColor' => 'accentSoftOrange3',
		];
	}

	public function getPlaceholderBackgroundColor(): string
	{
		return 'accentMainWarning';
	}
}
