<?php

namespace Bitrix\Intranet\User\Filter\Presets;

use Bitrix\Main\Filter\UserDataProvider;
use Bitrix\Main\Localization\Loc;

class AdminPreset extends FilterPreset
{

	public function getId(): string
	{
		return 'admin';
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_USER_LIST_FILTER_PRESET_ADMIN') ?? '';
	}

	public function getFilterFields(): array
	{
		return [
			'ADMIN' => 'Y'
		];
	}

	public function isDefault(): bool
	{
		return false;
	}
}