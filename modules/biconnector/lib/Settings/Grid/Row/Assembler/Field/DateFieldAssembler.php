<?php

namespace Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field;

use Bitrix\Main\Context;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Type;

class DateFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value)
	{
		if ($value instanceof Type\DateTime)
		{
			$userCulture = Context::getCurrent()?->getCulture();
			$currentDateFormat = ((new Type\DateTime())->format('Y') === $value->format('Y')) ? $userCulture?->getDayMonthFormat() : $userCulture?->getShortDateFormat();
			$value = $value->toUserTime();

			return FormatDate($currentDateFormat ?? '', $value->getTimestamp())
				. ' '
				. $value->format($userCulture?->getShortTimeFormat());
		}
		return $value;
	}
}
