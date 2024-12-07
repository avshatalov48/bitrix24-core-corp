<?php

namespace Bitrix\Crm\Feature\Category;

use Bitrix\Main\Localization\Loc;

class Common extends BaseCategory
{

	public function getName(): string
	{
		return Loc::getMessage('FEATURE_CATEGORY_COMMON_NAME');
	}

	public function getSort(): int
	{
		return 0;
	}
}
