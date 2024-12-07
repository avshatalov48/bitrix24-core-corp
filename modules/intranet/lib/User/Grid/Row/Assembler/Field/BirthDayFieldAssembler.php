<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;

class BirthDayFieldAssembler extends CustomUserFieldAssembler
{
	protected function prepareColumn($value): mixed
	{
		$birthdayFormat = Context::getCurrent()->getCulture()->getLongDateFormat();
		$showYearValue = Option::get("intranet", "user_profile_show_year", "Y");

		if (
			$showYearValue == 'N'
			|| (
				$value['PERSONAL_GENDER'] == 'F'
				&& $showYearValue == 'M'
			)
		)
		{
			$birthdayFormat = Context::getCurrent()->getCulture()->getDayMonthFormat();;
		}

		return $value['PERSONAL_BIRTHDAY'] ? FormatDate($birthdayFormat, $value['PERSONAL_BIRTHDAY']->getTimestamp()) : '';
	}
}