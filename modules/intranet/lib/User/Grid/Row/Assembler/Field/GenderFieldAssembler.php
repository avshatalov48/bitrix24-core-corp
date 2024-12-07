<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field;

use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;

class GenderFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): mixed
	{
		return !empty($value)
			? Loc::getMessage('INTRANET_USER_LIST_GENDER_' . $value)
			: '';
	}
}