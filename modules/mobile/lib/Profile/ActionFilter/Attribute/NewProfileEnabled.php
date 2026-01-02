<?php

namespace Bitrix\Mobile\Profile\ActionFilter\Attribute;

use Attribute;
use Bitrix\Main\Engine\ActionFilter\FilterType;
use Bitrix\Mobile\Profile\ActionFilter\NewProfileEnabledControl;

#[Attribute(Attribute::TARGET_METHOD)]
class NewProfileEnabled implements \Bitrix\Main\Engine\ActionFilter\Attribute\FilterAttributeInterface
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
			return [NewProfileEnabledControl::class];
		}

		return [new NewProfileEnabledControl()];
	}

	public function getType(): FilterType
	{
		return $this->type;
	}
}