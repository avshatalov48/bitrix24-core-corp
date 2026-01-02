<?php

namespace Bitrix\Crm\Feature\Category;

use Bitrix\Main\Localization\Loc;

final class Recurring extends BaseCategory
{
	public function getName(): string
	{
		return Loc::getMessage('FEATURE_CATEGORY_RECURRING_NAME');
	}

	public function getSort(): int
	{
		return 400;
	}
}

