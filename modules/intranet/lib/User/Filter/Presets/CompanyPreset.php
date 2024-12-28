<?php

namespace Bitrix\Intranet\User\Filter\Presets;

use Bitrix\Main\Filter\UserDataProvider;
use Bitrix\Main\Localization\Loc;

class CompanyPreset extends FilterPreset
{

	public function getId(): string
	{
		return UserDataProvider::extranetSite() ? 'employees' : 'company';
	}

	public function getName(): string
	{
		return UserDataProvider::extranetSite()
			? Loc::getMessage('INTRANET_USER_LIST_FILTER_PRESET_EMPLOYEES') ?? ''
			: Loc::getMessage('INTRANET_USER_LIST_FILTER_PRESET_COMPANY_MSG_1') ?? '';
	}

	public function getFilterFields(): array
	{
		return [
			'EXTRANET' => 'N',
			'COLLABER' => 'N',
			'VISITOR' => 'N',
			'FIRED' => 'N',
		];
	}

	public function isDefault(): bool
	{
		return true;
	}
}