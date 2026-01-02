<?php

namespace Bitrix\HumanResources\Internals\Command\Structure\Node\CreateDepartmentCommand\Result;

use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Result\PropertyResult;

class AddUsersToDepartmentStrategyResult extends PropertyResult
{
	public function __construct(
		public ?Node $node = null,
		public ?array $userMovedToRootIds = null,
	)
	{
		parent::__construct();
	}
}