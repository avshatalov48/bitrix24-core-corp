<?php

namespace Bitrix\Intranet\User\Filter\Presets;

use Bitrix\Main\Filter\UserDataProvider;
use Bitrix\Main\Localization\Loc;

class CollaberPreset extends FilterPreset
{
	public function getId(): string
	{
		return 'collaber';
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_USER_LIST_FILTER_PRESET_COLLABER') ?? '';
	}

	public function getFilterFields(): array
	{
		return [
			'COLLABER' => 'Y',
			'FIRED' => 'N',
		];
	}

	public function isDefault(): bool
	{
		return false;
	}

}