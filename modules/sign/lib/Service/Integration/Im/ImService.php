<?php

namespace Bitrix\Sign\Service\Integration\Im;

use Bitrix\Im\V2\Chat;
use Bitrix\Im\V2\Chat\CollabChat;
use Bitrix\Main;
use Bitrix\Sign\Contract\Chat\GroupChatMessage;
use Bitrix\Sign\Contract\Chat\Message\HasInitiator;
use Bitrix\Sign\Contract\Chat\Message;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Service\Sign\MemberService;
use CIMDisk;

class ImService
{
	private const IM_COMPONENT_ID = 'SignMessage';
	private const DOC_TITLE_LENGTH_LIMIT = 50;

	private MemberService $memberService;
	private DocumentService $documentService;

	public function __construct(
		?MemberService $memberService = null,
		?DocumentService $documentService = null,
	)
	{
		$this->memberService = $memberService ?? Container::instance()->getMemberService();
		$this->documentService = $documentService ?? Container::instance()->getDocumentService();
	}

	public function getById(int $id): ?Chat
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		if ($id < 1)
		{
			return null;
		}

		return Chat::getInstance($id);
	}

	public function getCollabById(int $id): ?CollabChat
	{
		$chat = $this->getById($id);

		if (!$chat instanceof CollabChat)
		{
			return null;
		}

		return $chat;
	}

	public function isUserHaveAccessToChat(Chat $chat, int $userId): bool
	{
		if (!$this->isAvailable())
		{
			return false;
		}

		if ($userId < 1)
		{
			return false;
		}

		$checkAccessResult = $chat->checkAccess($userId);

		return $checkAccessResult->isSuccess();
	}

	public function isAvailable(): bool
	{
		return
			Main\Loader::includeModule('im')
			&& \Bitrix\Im\V2\Service\Messenger::getInstance()->checkAccessibility()
		;
	}

	public function sendBFileToGroupChat(int $fileId, int $chatId, int $senderId, string $text = ''): Main\Result
	{
		if (!$this->isAvailable())
		{
			return (new Main\Result())->addError(new Main\Error(
				'The IM module is not available.'
			));
		}

		if ($fileId < 1)
		{
			return (new Main\Result())->addError(new Main\Error('Invalid sender fileId.'));
		}

		if ($chatId < 1)
		{
			return (new Main\Result())->addError(new Main\Error('Invalid chat id.'));
		}

		if ($senderId < 1)
		{
			return (new Main\Result())->addError(new Main\Error('Invalid sender id.'));
		}

		$diskFiles = CIMDisk::UploadFileFromMain($chatId, [$fileId]);

		if (!is_array($diskFiles))
		{
			return (new Main\Result())->addError(new Main\Error('Can\'t upload file to disk.'));
		}

		if (count($diskFiles) < 1)
		{
			return (new Main\Result())->addError(new Main\Error('Files not found.'));
		}

		$diskFilesWithPrefix = array_map(static fn (int $diskFileId): string => 'upload' . $diskFileId, $diskFiles);
		$result = CIMDisk::UploadFileFromDisk(
			$chatId,
			$diskFilesWithPrefix,
			$text,
			['USER_ID' => $senderId]
		);

		if (!is_array($result))
		{
			return (new Main\Result())->addError(new Main\Error('Can\'t send file to chat.'));
		}

		return new Main\Result();
	}

	public function sendGroupChatMessage(int $chatId, int $senderId, GroupChatMessage $message): Main\Result
	{
		if (!$this->isAvailable())
		{
			return (new Main\Result())->addError(new Main\Error(
				'The IM module is not available.'
			));
		}

		if ($chatId < 1)
		{
			return (new Main\Result())->addError(new Main\Error('Invalid chat id.'));
		}

		if ($senderId < 1)
		{
			return (new Main\Result())->addError(new Main\Error('Invalid sender id.'));
		}

		$chat = $this->getById($chatId);

		if ($chat === null)
		{
			return (new Main\Result())->addError(new Main\Error('Chat not found.'));
		}

		if (!$this->isUserHaveAccessToChat($chat, $senderId))
		{
			return (new Main\Result())->addError(new Main\Error('User does not have access to chat.'));
		}

		$result = \CIMMessenger::Add([
			'DIALOG_ID' => 'chat' . $chatId,
			'FROM_USER_ID' => $senderId,
			'MESSAGE' => $message->getText(),
			'PARAMS' => $message->getParams(),
		]);

		if (!$result)
		{
			return (new Main\Result())->addError(new Main\Error(
				'Can\'t send message to group chat'
			));
		}

		return new Main\Result();
	}

	public function sendMessage(Message $message): Main\Result
	{
		if (!$this->isAvailable())
		{
			return (new Main\Result())->addError(new Main\Error(
				'chat not available'
			));
		}

		// TODO fallback translated to receiver lang

		$result = \CIMMessenger::Add([
			'DIALOG_ID' => $message->getUserTo(),
			'FROM_USER_ID' => $message->getUserFrom(),
			'MESSAGE' => $message->getFallbackText(),
			'PARAMS' => $this->buildParams($message),
		]);

		if ($result === false)
		{
			return (new Main\Result())->addError(new Main\Error(
				'Can\'t add message to chat'
			));
		}

		return new Main\Result();
	}

	private function buildParams(Message $message): array
	{
		$params = [
			'COMPONENT_ID' => self::IM_COMPONENT_ID,
			'COMPONENT_PARAMS' => [
				'STAGE_ID' => $message->getStageId(),
				'DOCUMENT' => $this->buildSigningContext($message),
			],
		];

		if ($member = $message->getMember())
		{
			$params['COMPONENT_PARAMS']['USER'] = $this->buildUserContext($member);
		}

		if ($message instanceof HasInitiator)
		{
			$params['COMPONENT_PARAMS']['INITIATOR'] = $this->buildInitiatorContext($message);
		}

		if ($helpId = $message->getHelpId())
		{
			$params['COMPONENT_PARAMS']['HELP_ARTICLE'] = $helpId;
		}

		return $params;
	}

	private function buildUserContext(Member $member): array
	{
		return [
			'ID' => $this->memberService->getUserIdForMember($member),
			'NAME' => $this->memberService->getMemberRepresentedName($member),
		];
	}

	private function buildInitiatorContext(HasInitiator $message): array
	{
		return [
			'ID' => $message->getInitiatorUserId(),
			'NAME' => $message->getInitiatorName(),
		];
	}

	private function buildSigningContext(Message $message): array
	{
		$params = [];

		if ($document = $message->getDocument())
		{
			$params['ID'] = $document->id;
			$params['NAME'] = $this->documentService->getComposedTitleByDocument($document);
		}

		if ($message->getLink())
		{
			$params['LINK'] = $message->getLink();
		}

		return $params;
	}
}
