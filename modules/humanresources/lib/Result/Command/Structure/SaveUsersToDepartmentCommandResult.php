<?php

namespace Bitrix\HumanResources\Result\Command\Structure;

use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Result\PropertyResult;

class SaveUsersToDepartmentCommandResult extends PropertyResult
{
    public function __construct(
        public ?Node $node = null,
        public ?array $userMovedToRootIds = null,
    )
    {
        parent::__construct();
    }
}