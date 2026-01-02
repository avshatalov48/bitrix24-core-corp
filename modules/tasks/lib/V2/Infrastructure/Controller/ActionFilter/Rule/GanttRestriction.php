<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\ActionFilter\Rule;

use Attribute;
use Bitrix\Main\Engine\ActionFilter\Attribute\FilterAttributeInterface;
use Bitrix\Main\Engine\ActionFilter\FilterType;

#[Attribute(Attribute::TARGET_METHOD)]
class GanttRestriction implements FilterAttributeInterface
{
	public function getFilters(): array
	{
		return [(new \Bitrix\Tasks\V2\Infrastructure\Controller\ActionFilter\GanttRestriction())];
	}

	public function getType(): FilterType
	{
		return FilterType::EnablePrefilter;
	}
}
