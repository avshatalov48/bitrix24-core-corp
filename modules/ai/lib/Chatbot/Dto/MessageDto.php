<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Dto;

use Bitrix\AI\Chatbot\Enum\MessageType;

class MessageDto
{
	public function __construct(
		public readonly int $id,
		public readonly int $chatId,
		public readonly int $authorId,
		public readonly MessageType $type,
		public readonly string $content,
		public readonly array $params,
		public readonly string $dateCreate,
		public readonly bool $isSystem,
		public readonly bool $isViewed,
	)
	{
	}
}