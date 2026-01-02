<?php

namespace Bitrix\HumanResources\Integration\Im;

use Bitrix\HumanResources\Access\Model\ChatListModel;
use Bitrix\HumanResources\Access\Model\NodeModel;
use Bitrix\HumanResources\Access\StructureAccessController;
use Bitrix\HumanResources\Repository\NodeRelationRepository;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Result\Integration\Im\CreateChatResult;
use Bitrix\HumanResources\Result\Integration\Im\FilterChatResult;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\RelationEntitySubtype;
use Bitrix\HumanResources\Type\RelationEntityType;
use Bitrix\HumanResources\Type\StructureAction;
use Bitrix\Im\V2\Chat;
use Bitrix\Im\V2\Chat\ChatFactory;
use Bitrix\Im\V2\Permission\ActionGroup;
use Bitrix\Im\V2\Service\Messenger;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class ChatService
{
	private ?ChatFactory $chatFactory = null;
	private ?Messenger $messenger = null;
	private NodeRelationRepository $nodeRelationRepository;

	public function __construct(
		?ChatFactory $chatFactory = null
	)
	{
		$this->nodeRelationRepository = Container::getNodeRelationRepository();
		if ($this->isAvailable())
		{
			$this->chatFactory = $chatFactory ?? ChatFactory::getInstance();
			$this->messenger = Messenger::getInstance();
		}
	}

	public function isAvailable(): bool
	{
		return Loader::includeModule('im');
	}

	/**
	 * Create chat, channel, or other type of b_im_chat by calling ChatFactory
	 *
	 * @param Item\Node $node
	 * @param array<int> $headIds
	 * @param RelationEntitySubtype $type
	 * @return CreateChatResult
	 */
	public function createChat(Item\Node $node, array $headIds, RelationEntitySubtype $type): CreateChatResult
	{
		$result = new CreateChatResult();

		if (!$this->isAvailable())
		{
			return $result->addError(new Error(Loc::getMessage('HUMANRESOURCES_CHAT_SERVICE_NOT_AVAILABLE_MSGVER_1')));
		}

		$creationType = $type === RelationEntitySubtype::Channel ? Chat::IM_TYPE_CHANNEL : Chat::IM_TYPE_CHAT;
		$title = $node->name;

		if (count($headIds) === 0)
		{
			if ($type === RelationEntitySubtype::Channel)
			{
				return $result->addError(new Error(Loc::getMessage('HUMANRESOURCES_CHAT_SERVICE_CREATION_ERROR_CHANNEL')));
			}
			else
			{
				return $result->addError(new Error(Loc::getMessage('HUMANRESOURCES_CHAT_SERVICE_CREATION_ERROR_CHAT')));
			}
		}
		$ownerKey = array_rand($headIds);

		/**
		 * Parameters for chatFactory's addChat method
		 *
		 * not included but can be useful:
		 * 'MEMBER_ENTITIES' => [['department', $node->accessCode]], // we can pass this and relation will be created by im.
		 * 		But it's better to create relation in humanresources module due to asynchronous creation of accessCode
		 * 		(see NewToOldEventHandler::onNodeAdded)
		 * 'DESCRIPTION' - it is possible to add in the future
		 * 'SKIP_ADD_MESSAGE' => 'Y/N' - it is possible to add in the future
		 * 'USERS' => $userIds - test and think about, should we pass it directly or let sync mechanism add users to chat
		 */
		$params = [
			'TYPE' => $creationType,
			'OWNER_ID' => $headIds[$ownerKey],
			'AUTHOR_ID' => $headIds[$ownerKey], // if won't pass, current user will be author_id, but he can be not in the department
			'MANAGERS' => $headIds,
			'TITLE' => $title,
		];

		$addResult = $this->chatFactory->addChat($params);
		if (!$addResult->isSuccess())
		{
			return $result->addError($addResult->getError());
		}

		$result->setData($addResult->getResult());
		if (!($result->getChat() instanceof Chat))
		{
			if ($type === RelationEntitySubtype::Channel)
			{
				return $result->addError(new Error(Loc::getMessage('HUMANRESOURCES_CHAT_SERVICE_CREATION_ERROR_CHANNEL')));
			}
			else
			{
				return $result->addError(new Error(Loc::getMessage('HUMANRESOURCES_CHAT_SERVICE_CREATION_ERROR_CHAT')));
			}
		}

		return $result;
	}

	/**
	 * Filters chat ids checking if user has permission to add users in this chat and chat is of a correct type.
	 * 	Permission is dependent on user role in chat (author, member, or manager) and MANAGE_USERS_ADD chat field
	 * 	Check type so we don't and up with chats or channels with a wrong subcategory
	 *
	 * @param array $ids - chat ids (chats, channels, etc.)
	 * @param int $userId - user id whose rights will be checked
	 * @param ?RelationEntitySubtype $type
	 * @return FilterChatResult - result with filtered ids or error
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function filterByPermissionsAndType(array $ids, int $userId, ?RelationEntitySubtype $type = null): FilterChatResult
	{
		$result = new FilterChatResult();

		if (count($ids) === 0)
		{
			return $result->setChatIds([]);
		}

		if (!$this->isAvailable() && count($ids) > 0)
		{
			return $result->addError(new Error(Loc::getMessage('HUMANRESOURCES_CHAT_SERVICE_NOT_AVAILABLE_MSGVER_1')));
		}

		$filter = Chat\Filter::init()
			->filterByIds($ids)
			->filterByPermissions($userId, ActionGroup::ManageUsersAdd)
			->filterByEntityType(null)
		;

		if ($type)
		{
			$chatTypes = $type === RelationEntitySubtype::Channel
				? [Chat::IM_TYPE_CHANNEL, Chat::IM_TYPE_OPEN_CHANNEL]
				: [Chat::IM_TYPE_CHAT, Chat::IM_TYPE_OPEN];
			$filter->filterByTypes($chatTypes);
		}

		$chatIds = $filter->getIds();

		return $result->setChatIds($chatIds);
	}

	public function getChatsAndChannelsByNode(Item\Node $node, ?int $userId = null): array
	{
		$chatCollection = $this->nodeRelationRepository->findRelationsByNodeIdAndRelationType(
			nodeId: $node->id,
			relationEntityType: RelationEntityType::CHAT,
			limit: 0,
		);

		$chatIds = [];
		$channelIds = [];
		$indirectRelations = [];
		foreach ($chatCollection->getValues() as $chat)
		{
			if ($chat->entitySubtype === RelationEntitySubtype::Chat)
			{
				$chatIds[] = $chat->entityId;
			}

			if ($chat->entitySubtype === RelationEntitySubtype::Channel)
			{
				$channelIds[] = $chat->entityId;
			}

			if ($chat->nodeId !== $node->id)
			{
				$indirectRelations[$chat->entityId] = $chat->nodeId;
			}
		}

		$userId = $userId ?? CurrentUser::get()->getId();

		$chatList = $this->getChatsByIds($chatIds, $userId, $indirectRelations);
		$channelsList = $this->getChatsByIds($channelIds, $userId, $indirectRelations);

		return [
			'chats' => array_values(array_filter($chatList, fn($chat) => $chat['hasAccess'] === true)),
			'channels' => array_values(array_filter($channelsList, fn($channel) => $channel['hasAccess'] === true)),
			'chatsNoAccess' => count(array_filter($chatList, fn($chat) => $chat['hasAccess'] === false)),
			'channelsNoAccess' => count(array_filter($channelsList, fn($channel) => $channel['hasAccess'] === false)),
		];
	}

	/**
	 * Returns chats or channels data by ids
	 *
	 * @param array<int> $ids
	 * @param int $userId
	 * @param array<int> $indirectRelations
	 * @param bool $checkChatPermission
	 *
	 * @return array
	 */
	private function getChatsByIds(array $ids, int $userId, array $indirectRelations = []): array
	{
		$result = [];
		if (!$this->isAvailable() || empty($ids))
		{
			return $result;
		}

		$chats = $this->messenger->getChats($ids);

		if (empty($chats))
		{
			return $result;
		}

		foreach ($chats as $chat)
		{
			if (in_array($chat->getEntityType(), $this->getGeneralChatTypes(), true))
			{
				continue;
			}

			$result[] = $this->getChatEntityBaseInfo($chat, $userId, $indirectRelations);
		}

		return $result;
	}

	/**
	 * @param Chat $chat
	 * @param int $userId
	 * @param array<int> $indirectRelations
	 * @param bool $checkIndividualPermission
	 *
	 * @return array
	 */
	private function getChatEntityBaseInfo(Chat $chat, int $userId, array $indirectRelations = []): array
	{
		if (!$chat->checkAccess($userId)->isSuccess())
		{
			return [
				'hasAccess' => false,
			];
		}

		return [
			'id' => $chat->getId(),
			'dialogId' => $chat->getDialogId(),
			'title' => $chat->getTitle(),
			'subtitle' => $this->getSubTitleByChatType($chat->getType()) ?? '',
			'avatar' => $chat->getAvatar(),
			'color' => $chat->getColor(true),
			'type' => RelationEntitySubtype::fromChatType($chat->getType())?->value,
			'isExtranet' => $chat->getExtranet() ?? false,
			'originalNodeId' => $indirectRelations[$chat->getId()] ?? null,
			'hasAccess' => true,
		];
	}

	private function getSubTitleByChatType(string $type): ?string
	{
		return match ($type)
		{
			Chat::IM_TYPE_OPEN => Loc::getMessage('HUMANRESOURCES_CHAT_SERVICE_CHAT_TYPE_OPEN_CHAT_SUBTITLE'),
			Chat::IM_TYPE_CHAT => Loc::getMessage('HUMANRESOURCES_CHAT_SERVICE_CHAT_TYPE_PRIVATE_CHAT_SUBTITLE'),
			Chat::IM_TYPE_OPEN_CHANNEL => Loc::getMessage('HUMANRESOURCES_CHAT_SERVICE_CHAT_TYPE_OPEN_CHANNEL_SUBTITLE'),
			Chat::IM_TYPE_CHANNEL => Loc::getMessage('HUMANRESOURCES_CHAT_SERVICE_CHAT_TYPE_PRIVATE_CHANNEL_SUBTITLE'),
			default => '',
		};
	}

	private function getGeneralChatTypes(): array
	{
		return [
			Chat::ENTITY_TYPE_GENERAL,
			Chat::ENTITY_TYPE_GENERAL_CHANNEL,
		];
	}
}
