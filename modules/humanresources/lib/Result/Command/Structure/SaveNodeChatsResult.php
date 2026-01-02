<?php

namespace Bitrix\HumanResources\Result\Command\Structure;

use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Result\PropertyResult;

class SaveNodeChatsResult extends PropertyResult
{
	public function __construct(
		public ?Node $node = null
	)
	{
		parent::__construct();
	}
}