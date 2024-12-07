<?php

namespace Bitrix\Crm\Feature\Category;

use Bitrix\Main\Localization\Loc;

class Permissions extends BaseCategory
{

	public function getName(): string
	{
		return Loc::getMessage('FEATURE_CATEGORY_PERMISSIONS_NAME');
	}

	public function getSort(): int
	{
		return 100;
	}
}
