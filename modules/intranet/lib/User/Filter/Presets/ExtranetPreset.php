<?php

namespace Bitrix\Intranet\User\Filter\Presets;

use Bitrix\Main\Filter\UserDataProvider;
use Bitrix\Main\Localization\Loc;

class ExtranetPreset extends FilterPreset
{
	public function getId(): string
	{
		return 'extranet';
	}

	public function getName(): string
	{
		return UserDataProvider::extranetSite()
			? Loc::getMessage('INTRANET_USER_LIST_FILTER_PRESET_CONTACTS') ?? ''
			: Loc::getMessage('INTRANET_USER_LIST_FILTER_PRESET_EXTRANET') ?? '';
	}

	public function getFilterFields(): array
	{
		return [
			'EXTRANET' => 'Y',
			'FIRED' => 'N'
		];
	}

	public function isDefault(): bool
	{
		return false;
	}

}