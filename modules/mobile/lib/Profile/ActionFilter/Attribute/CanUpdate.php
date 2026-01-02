<?php

namespace Bitrix\Mobile\Profile\ActionFilter\Attribute;

use Attribute;
use Bitrix\Main\Engine\ActionFilter\FilterType;
use Bitrix\Mobile\Profile\ActionFilter\CanUpdateControl;

#[Attribute(Attribute::TARGET_METHOD)]
class CanUpdate implements \Bitrix\Main\Engine\ActionFilter\Attribute\FilterAttributeInterface
{
	public function __construct(
		private readonly FilterType $type = FilterType::EnablePrefilter,
	)
	{

	}

	public function getFilters(): array
	{
		if ($this->type->isNegative())
		{
			return [CanUpdateControl::class];
		}

		return [new CanUpdateControl()];
	}

	public function getType(): FilterType
	{
		return $this->type;
	}
}