<?php

namespace Bitrix\HumanResources\Controller\Structure\Node\Member;

use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\HumanResources\Command\Structure\Node\SaveNodeChatsCommand;
use Bitrix\HumanResources\Engine\Controller;
use Bitrix\HumanResources\Integration\Im\ChatService;
use Bitrix\HumanResources\Integration\Socialnetwork\CollabService;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Exception\CommandException;
use Bitrix\HumanResources\Exception\CommandValidateException;
use Bitrix\HumanResources\Type\AccessibleItemType;
use Bitrix\HumanResources\Type\NodeChatType;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Request;
use Bitrix\HumanResources\Internals\Attribute;

class Chat extends Controller
{
	private readonly ChatService $chatService;
	private readonly CollabService $collabService;

	public function __construct(Request $request = null)
	{
		$this->chatService = Container::getChatService();
		$this->collabService = Container::getCollabService();
		parent::__construct($request);
	}

	#[Attribute\Access\LogicOr(
		new Attribute\StructureActionAccess(
			permission: StructureActionDictionary::ACTION_DEPARTMENT_CHAT_EDIT,
			itemType: AccessibleItemType::CHAT_LIST,
			itemIdRequestKey: 'nodeId',
		),
		new Attribute\StructureActionAccess(
			permission: StructureActionDictionary::ACTION_TEAM_CHAT_EDIT,
			itemType: AccessibleItemType::CHAT_LIST,
			itemIdRequestKey: 'nodeId',
		),
	)]
	public function saveChatListAction(
		Item\Node $node,
		bool $createDefault = false,
		array $ids = [],
		array $removeIds = [],
		bool $withChildren = false,
	): void
	{
		try
		{
			$commandResult = (new SaveNodeChatsCommand(
				node: $node,
				chatType: NodeChatType::Chat,
				createDefault: $createDefault,
				ids: $ids,
				removeIds: $removeIds,
				withChildren: $withChildren
			))->run();

			if (!$commandResult->isSuccess())
			{
				$this->addErrors($commandResult->getErrors());
			}
		}
		catch (CommandException|CommandValidateException $e)
		{
			$this->addError(new Error($e->getMessage()));
		}
	}


	#[Attribute\Access\LogicOr(
		new Attribute\StructureActionAccess(
			permission: StructureActionDictionary::ACTION_DEPARTMENT_CHANNEL_EDIT,
			itemType: AccessibleItemType::CHAT_LIST,
			itemIdRequestKey: 'nodeId',
		),
		new Attribute\StructureActionAccess(
			permission: StructureActionDictionary::ACTION_TEAM_CHANNEL_EDIT,
			itemType: AccessibleItemType::CHAT_LIST,
			itemIdRequestKey: 'nodeId',
		),
	)]
	public function saveChannelListAction(
		Item\Node $node,
		bool $createDefault = false,
		array $ids = [],
		array $removeIds = [],
		bool $withChildren = false,
	): void
	{
		try
		{
			$commandResult = (new SaveNodeChatsCommand(
				node: $node,
				chatType: NodeChatType::Channel,
				createDefault: $createDefault,
				ids: $ids,
				removeIds: $removeIds,
				withChildren: $withChildren
			))->run();

			if (!$commandResult->isSuccess())
			{
				$this->addErrors($commandResult->getErrors());
			}
		}
		catch (CommandException|CommandValidateException $e)
		{
			$this->addError(new Error($e->getMessage()));
		}
	}


	#[Attribute\Access\LogicOr(
		new Attribute\StructureActionAccess(
			permission: StructureActionDictionary::ACTION_DEPARTMENT_COLLAB_EDIT,
			itemType: AccessibleItemType::CHAT_LIST,
			itemIdRequestKey: 'nodeId',
		),
		new Attribute\StructureActionAccess(
			permission: StructureActionDictionary::ACTION_TEAM_COLLAB_EDIT,
			itemType: AccessibleItemType::CHAT_LIST,
			itemIdRequestKey: 'nodeId',
		),
	)]
	public function saveCollabListAction(
		Item\Node $node,
		bool $createDefault = false,
		array $ids = [],
		array $removeIds = [],
		bool $withChildren = false,
	): void
	{
		try
		{
			$commandResult = (new SaveNodeChatsCommand(
				node: $node,
				chatType: NodeChatType::Collab,
				createDefault: $createDefault,
				ids: $ids,
				removeIds: $removeIds,
				withChildren: $withChildren
			))->run();

			if (!$commandResult->isSuccess())
			{
				$this->addErrors($commandResult->getErrors());
			}
		}
		catch (CommandException|CommandValidateException $e)
		{
			$this->addError(new Error($e->getMessage()));
		}
	}

	public function getListAction(Item\Node $node): array
	{
		$chatService = Container::getChatService();
		if (!$chatService->isAvailable())
		{
			return [];
		}

		$chatsResult = $this->chatService->getChatsAndChannelsByNode($node);
		$collabsResult = $this->collabService->getCollabsByNode($node, CurrentUser::get()->getId());

		return array_merge($chatsResult, $collabsResult);
	}
}
