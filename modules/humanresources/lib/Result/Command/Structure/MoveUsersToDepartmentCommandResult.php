<?php

namespace Bitrix\HumanResources\Result\Command\Structure;

use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Result\PropertyResult;

class MoveUsersToDepartmentCommandResult extends PropertyResult
{
    public function __construct(
        public ?Node $node = null,
		public ?array $updatedDepartmentIds = null,
        public ?int $userCount = null,
    )
    {
        parent::__construct();
    }
}