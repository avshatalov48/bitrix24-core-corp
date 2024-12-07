<?php

namespace Bitrix\Intranet\User\Filter\Presets;

use Bitrix\Intranet\User\Filter\Provider\IntranetUserDataProvider;
use Bitrix\Main\Filter\UserDataProvider;
use Bitrix\Main\Localization\Loc;

class WaitConfirmationPreset extends FilterPreset
{

	public function getId(): string
	{
		return 'wait_confirmation';
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_USER_LIST_FILTER_PRESET_WAIT_CONFIRMATION') ?? '';
	}

	public function getFilterFields(): array
	{
		return [
			'WAIT_CONFIRMATION' => 'Y',
		];
	}

	public function isDefault(): bool
	{
		return false;
	}
}
