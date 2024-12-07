<?php

namespace Bitrix\Crm\Feature\Category;

use Bitrix\Main\Localization\Loc;

class OperationsApi extends BaseCategory
{

	public function getName(): string
	{
		return Loc::getMessage('CATEGORY_OPERATIONS_API_NAME');
	}

	public function getSort(): int
	{
		return 100;
	}
}
