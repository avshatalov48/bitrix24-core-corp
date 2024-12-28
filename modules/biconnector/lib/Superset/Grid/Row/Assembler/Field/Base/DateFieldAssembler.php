<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Base;

use Bitrix\Main\Context;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;

class DateFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value)
	{
		if ($value instanceof Type\DateTime)
		{
			$now = time() + \CTimeZone::getOffset();
			$timestamp = (int)$value->toUserTime()->getTimestamp();

			$fullDate = formatDate('FULL', $timestamp, $now);
			if ($now - $timestamp < 60)
			{
				$readableDate = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DATE_NOW');
			}
			else
			{
				$userCulture = Context::getCurrent()?->getCulture();
				$isCurrentYear = (date('Y') === date('Y', $timestamp));
				$dateFormat = $isCurrentYear ? $userCulture?->getDayMonthFormat() : $userCulture?->getLongDateFormat();

				$nowDate = (new Type\DateTime())->toUserTime()->setTime(0, 0);
				$diff = $nowDate->getDiff($value);
				if ($diff->days < 2)
				{
					$dateFormat = 'x';
				}
				$readableDate = formatDate($dateFormat, $timestamp, $now);
			}

			return <<<HTML
<span 
	data-hint="{$fullDate}" 
	data-hint-no-icon
	data-hint-interactivity
	data-hint-center
>
	$readableDate
</span>
HTML;
		}

		return $value;
	}
}