<?php

namespace Bitrix\Crm\Feature\Category;

use Bitrix\Main\Localization\Loc;

class Activities extends BaseCategory
{

	public function getName(): string
	{
		return Loc::getMessage('FEATURE_CATEGORY_ACTIVITIES_NAME');
	}

	public function getSort(): int
	{
		return 200;
	}
}
