<?php

namespace Bitrix\Crm\Feature;

use Bitrix\Main\Localization\Loc;

class ReturnToOldDetailPage extends BaseFeature
{
	public function getName(): string
	{
		return Loc::getMessage('RETURN_TO_OLD_DETAIL_PAGE_NAME');
	}

	public function allowSwitchBySecretLink(): bool
	{
		return false;
	}

	protected function getOptionName(): string
	{
		return 'allow_old_detail_page';
	}
}
