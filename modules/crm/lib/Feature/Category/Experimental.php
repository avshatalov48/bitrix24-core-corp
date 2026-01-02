<?php

namespace Bitrix\Crm\Feature\Category;

use Bitrix\Main\Localization\Loc;

final class Experimental extends BaseCategory
{
	public function getName(): string
	{
		return Loc::getMessage('FEATURE_CATEGORY_EXPERIMENTAL_NAME');
	}
	
	public function getSort(): int
	{
		return 900;
	}
}
