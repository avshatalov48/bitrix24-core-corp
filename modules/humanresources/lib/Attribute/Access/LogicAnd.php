<?php

namespace Bitrix\HumanResources\Attribute\Access;

use Attribute;
use Bitrix\HumanResources\Attribute\StructureActionAccess;

#[Attribute(flags: Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class LogicAnd
{
	/**
	 * @var list<StructureActionAccess>
	 */
	public readonly array $conditions;

	public function __construct(
		StructureActionAccess ...$conditions,
	)
	{
		$this->conditions = $conditions;
	}
}