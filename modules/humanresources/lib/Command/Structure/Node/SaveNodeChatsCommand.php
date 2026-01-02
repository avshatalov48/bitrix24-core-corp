<?php

namespace Bitrix\HumanResources\Command\Structure\Node;

use Bitrix\HumanResources\Command\AbstractCommand;
use Bitrix\HumanResources\Command\Structure\Node\Handler\SaveNodeChatsCommandHandler;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Result\Command\Structure\SaveNodeChatsResult;
use Bitrix\HumanResources\Type\NodeChatType;
use Bitrix\Main\Error;
use Bitrix\HumanResources\Item;

/**
 * @extends AbstractCommand<SaveNodeChatsResult>
 */
class SaveNodeChatsCommand extends AbstractCommand
{
	const CHAT_INDEX = 'chat';
	const CHANNEL_INDEX = 'channel';
	const COLLAB_INDEX = 'collab';
	const WITH_CHILDREN_INDEX = 'withChildren';

	/**
	 * @param Node $node
	 * @param NodeChatType $chatType
	 * @param bool $createDefault
	 * @param array $ids
	 * @param array $removeIds
	 * @param bool $withChildren
	 */
	public function __construct(
		public readonly Item\Node $node,
		public readonly NodeChatType $chatType,
		public readonly bool $createDefault = false,
		public readonly array $ids = [],
		public readonly array $removeIds = [],
		public readonly bool $withChildren = false,
	)
	{}

	protected function validate(): bool
	{
		if (!array_product(array_map('is_numeric', $this->ids))
			|| !array_product(array_map('is_numeric', $this->removeIds))
		)
		{
			return false;
		}

		return true;
	}

	protected function execute(): SaveNodeChatsResult
	{
		try
		{
			return (new SaveNodeChatsCommandHandler())($this);
		}
		catch (\Exception $e)
		{
			$result = (new SaveNodeChatsResult());
			$result->addError(
				new Error(
					$e->getMessage(),
					$e->getCode(),
				),
			);

			return $result;
		}
	}
}
