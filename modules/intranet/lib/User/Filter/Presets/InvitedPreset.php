<?php

namespace Bitrix\Intranet\User\Filter\Presets;

use Bitrix\Main\Filter\UserDataProvider;
use Bitrix\Main\Localization\Loc;

class InvitedPreset extends FilterPreset
{
	public function getId(): string
	{
		return 'invited';
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_USER_LIST_FILTER_PRESET_INVITED_MSG_1') ?? '';
	}

	public function getFilterFields(): array
	{
		return [
			'INVITED' => 'Y',
			'FIRED' => 'N'
		];
	}

	public function isDefault(): bool
	{
		return false;
	}
}