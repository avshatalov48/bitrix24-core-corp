<?php

namespace Bitrix\Sign\Service\Sign\DocumentChat;

use Bitrix\Sign\Type\Integration\Im\DocumentChatType;
use Bitrix\Sign\Type\MemberStatus;

class ChatTypeConverterService
{
	/**
	 * @return list<MemberStatus::*>
	 */
	public function convertDocumentChatTypeToMemberStatuses(DocumentChatType $type): array
	{
		return match ($type)
		{
			DocumentChatType::WAIT => [MemberStatus::WAIT],
			DocumentChatType::READY => [MemberStatus::READY, MemberStatus::STOPPABLE_READY],
			DocumentChatType::STOPPED => [MemberStatus::REFUSED, MemberStatus::STOPPED],
			default => [],
		};
	}

	public function convertMemberStatusesToDocumentChatType(string $memberStatus): ?DocumentChatType
	{
		return match ($memberStatus)
		{
			MemberStatus::WAIT => DocumentChatType::WAIT,
			MemberStatus::READY, MemberStatus::STOPPABLE_READY => DocumentChatType::READY,
			MemberStatus::REFUSED, MemberStatus::STOPPED => DocumentChatType::STOPPED,
			default => null,
		};
	}
}