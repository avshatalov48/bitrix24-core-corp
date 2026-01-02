<?php

namespace Bitrix\Mobile\Profile\ActionFilter\Attribute;

use Attribute;
use Bitrix\Main\Engine\ActionFilter\FilterType;
use Bitrix\Mobile\Profile\ActionFilter\CanViewControl;

#[Attribute(Attribute::TARGET_METHOD)]
class CanView implements \Bitrix\Main\Engine\ActionFilter\Attribute\FilterAttributeInterface
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
			return [CanViewControl::class];
		}

		return [new CanViewControl()];
	}

	public function getType(): FilterType
	{
		return $this->type;
	}
}