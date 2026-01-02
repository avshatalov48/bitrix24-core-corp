<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Dto;

use Bitrix\Main\HttpRequest;
use Bitrix\Main\Validation\Rule\AtLeastOnePropertyNotEmpty;
use Bitrix\Main\Validation\Rule\NotEmpty;

#[AtLeastOnePropertyNotEmpty(['chatId', 'entityId'])]
final class ChatInitDto
{
	public function __construct(
		#[NotEmpty]
		readonly public string $scenarioCode,

		readonly public ?int $chatId,

		#[NotEmpty]
		readonly public string $entityType,

		readonly public ?string $entityId,

		#[NotEmpty]
		readonly public int $userId,

		readonly public array $parameters
	)
	{}

	public static function createFromRequest(HttpRequest $request, int $userId): self
	{
		return new self(
			scenarioCode: (string)$request->get('scenarioCode'),
			chatId: (int)$request->get('chatId'),
			entityType: (string)$request->get('entityType'),
			entityId: (string)$request->get('entityId'),
			userId: $userId,
			parameters: $request->get('parameters') ?? []
		);
	}
}