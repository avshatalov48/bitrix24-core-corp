<?php

namespace Bitrix\Sign\Service\Integration\Im;

use Bitrix\Main;
use Bitrix\Sign\Contract\Chat\Message\HasInitiator;
use Bitrix\Sign\Contract\Chat\Message;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Service\Sign\MemberService;

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

	public function isAvailable(): bool
	{
		return
			Main\Loader::includeModule('im')
			&& \Bitrix\Im\V2\Service\Messenger::getInstance()->checkAccessibility()
		;
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
