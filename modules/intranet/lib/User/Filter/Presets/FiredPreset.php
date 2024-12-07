<?php

namespace Bitrix\Intranet\User\Filter\Presets;

use Bitrix\Main\Filter\UserDataProvider;
use Bitrix\Main\Localization\Loc;

class FiredPreset extends FilterPreset
{
	public function getId(): string
	{
		return 'fired';
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_USER_LIST_FILTER_PRESET_FIRED') ?? '';
	}

	public function getFilterFields(): array
	{
		return [
			'FIRED' => 'Y'
		];
	}

	public function isDefault(): bool
	{
		return false;
	}
}