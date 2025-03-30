<?php

namespace Bitrix\Crm\Feature\Category;

use Bitrix\Main\Localization\Loc;

class Legacy extends BaseCategory
{

	public function getName(): string
	{
		return Loc::getMessage('CATEGORY_LEGACY_NAME');
	}

	public function getSort(): int
	{
		return 1000;
	}
}
