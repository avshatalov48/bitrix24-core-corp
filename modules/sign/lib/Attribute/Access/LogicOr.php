<?php

namespace Bitrix\Sign\Attribute\Access;

use \Attribute;
use Bitrix\Sign\Attribute\ActionAccess;

#[Attribute(flags: Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class LogicOr
{
	/**
	 * @var list<ActionAccess>
	 */
	public readonly array $conditions;

	public function __construct(
		ActionAccess ...$conditions,
	)
	{
		$this->conditions = $conditions;
	}
}