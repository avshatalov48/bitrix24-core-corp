<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Command\Structure\Node;

use Bitrix\HumanResources\Command\AbstractCommand;
use Bitrix\HumanResources\Command\Structure\Node\Handler\CreateNodeCommandHandler;
use Bitrix\HumanResources\Result\Command\Structure\CreateNodeCommandResult;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Main\Error;
use Bitrix\Main;
use Bitrix\HumanResources\Command\Structure\Node\Enum\UserAddStrategy;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Main\Validation\Rule\NotEmpty;
use Bitrix\HumanResources\Item;

/**
 * @extends AbstractCommand<CreateNodeCommandResult>
 */
class CreateNodeCommand extends AbstractCommand
{

	public ?Item\Node $node = null;

	public function __construct(
		#[PositiveNumber]
		public readonly int $structureId,
		#[NotEmpty]
		public readonly string $name,
		public readonly NodeEntityType $entityType,
		#[PositiveNumber]
		public readonly int $parentId,
		public readonly ?string $description = null,
		public ?string $colorName = null,
		public readonly UserAddStrategy $usersStrategy = UserAddStrategy::MoveUsersStrategy,
		public readonly array $userIds = [],
		public readonly bool $createChat = true,
		public readonly array $bindingChatIds = [],
		public readonly bool $createChannel = true,
		public readonly array $bindingChannelIds = [],
		public readonly bool $createCollab = false,
		public readonly array $bindingCollabIds = [],
		public readonly array $settings = [],
	)
	{
	}

	protected function execute(): Main\Result
	{
		try
		{
			return (new CreateNodeCommandHandler())($this);
		}
		catch (\Exception $e)
		{
			$result = (new Main\Result());
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