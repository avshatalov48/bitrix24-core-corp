<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Command\Structure\Node;

use Bitrix\HumanResources\Command\AbstractCommand;
use Bitrix\HumanResources\Command\Structure\Node\Handler\NodeOrderCommandHandler;
use Bitrix\HumanResources\Item\Node;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class NodeOrderCommand extends AbstractCommand
{
	public const ORDER_STEP = 100;
	/**
	 * @param Node $node
	 * @param int $direction
	 * @param int $count
	 */
	public function __construct(
		public readonly Node $node,
		public readonly  int $direction,
		public readonly  int $count,
	)
	{
	}

	protected function execute(): Result
	{
		try
		{
			(new NodeOrderCommandHandler())($this);
		}
		catch (\Exception $e)
		{
			return (new Result())->addError(
				new Error(
					$e->getMessage(),
					$e->getCode(),
				),
			);
		}

		return new Result();
	}
}