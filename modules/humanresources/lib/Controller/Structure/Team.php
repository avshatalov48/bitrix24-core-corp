<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Controller\Structure;

use Bitrix\HumanResources\Command\Structure\Node\Enum\UserAddStrategy;
use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\HumanResources\Command\Structure\Node\CreateNodeCommand;
use Bitrix\HumanResources\Config\Feature;
use Bitrix\HumanResources\Engine\Controller;
use Bitrix\HumanResources\Exception\CommandException;
use Bitrix\HumanResources\Exception\CommandValidateException;
use Bitrix\HumanResources\Item\Structure;
use Bitrix\HumanResources\Type\AccessibleItemType;
use Bitrix\HumanResources\Internals\Attribute;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Validation\ValidationService;

class Team extends Controller
{
	private ValidationService $validation;

	protected function init(): void
	{
		parent::init();

		$this->validation = ServiceLocator::getInstance()->get('main.validation.service');
	}

	/**
	 * @param string $name
	 * @param int $parentId
	 * @param Structure $structure
	 * @param string $colorName
	 * @param array $userIds
	 * @param string|null $description
	 * @param bool $createChat
	 * @param array $bindingChatIds
	 * @param bool $createChannel
	 * @param array $bindingChannelIds
	 * @param array $settings
	 *
	 * @return array
	 *
	 * @throws CommandException
	 * @throws CommandValidateException
	 */
	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_TEAM_CREATE,
		itemType: AccessibleItemType::NODE,
		itemParentIdRequestKey: 'parentId',
	)]
	public function createAction(
		string $name,
		int $parentId,
		Item\Structure $structure,
		string $colorName,
		array $userIds = [],
		?string $description = null,
		bool $createChat = false,
		array $bindingChatIds = [],
		bool $createChannel = false,
		array $bindingChannelIds = [],
		bool $createCollab = false,
		array $bindingCollabIds = [],
		array $settings = [],
	): array
	{
		$usersStrategy = UserAddStrategy::SaveUsersStrategy;

		$areCollabsAvailable = Feature::instance()->isCollabsAvailable();

		$command = new CreateNodeCommand(
			$structure->id,
			$name,
			NodeEntityType::TEAM,
			$parentId,
			$description,
			$colorName,
			$usersStrategy,
			$userIds,
			$createChat,
			$bindingChatIds,
			$createChannel,
			$bindingChannelIds,
			$areCollabsAvailable ? $createCollab : false,
			$areCollabsAvailable ? $bindingCollabIds : [],
			$settings,
		);

		$validationResult = $this->validation->validate($command);

		if (!$validationResult->isSuccess())
		{
			$this->addErrors($validationResult->getErrors());

			return [];
		}

		$commandResult = $command->run();

		if (!$commandResult->isSuccess())
		{
			$this->addErrors($commandResult->getErrors());

			return [];
		}

		return $commandResult->getData();
	}
}