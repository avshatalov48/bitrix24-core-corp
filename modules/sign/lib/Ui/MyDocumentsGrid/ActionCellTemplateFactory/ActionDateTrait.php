<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory;

use Bitrix\Main\Context;
use Bitrix\Main\Type\DateTime;

trait ActionDateTrait
{
	protected static function getFormattedDate(?DateTime $date): ?string
	{
		$format = Context::getCurrent()?->getCulture()?->getLongDateFormat() ?? "j F Y";

		return $date !== null ? FormatDate($format, $date->getTimestamp()) : null;
	}
}